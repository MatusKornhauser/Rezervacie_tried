REZERVÁCIE UČEBNÍ
=================

Webová aplikácia na rezervovanie školských učební (PHP + MySQL, bez frameworku).
Učitelia sa prihlásia školským emailom (@centrumnadania.sk), vyberú dátum,
učebňu a čas v 30-minútových blokoch a server skontroluje, či sa rezervácia
neprekrýva s existujúcou. Frontend je jedna stránka s vanilla JS, ktorá
komunikuje s PHP endpointami cez fetch() a JSON.


1. ARCHITEKTÚRA
---------------
Klasická "stránka + JSON API" štruktúra, každý endpoint je samostatný .php súbor.

  Stránky (vracajú HTML):
    auth.php              prihlásenie + registrácia (taby), POST spracúva priamo
                          v sebe; login.php a register.php iba presmerujú sem
    index.php             formulár na rezerváciu + mesačný kalendár obsadenosti
    my_reservations.php   zoznam vlastných rezervácií, rušenie
    logout.php            zničí session, presmeruje na login

  JSON endpointy (vracajú {ok: true|false, ...}):
    get_classrooms.php, get_reservations.php, get_my_reservations.php,
    create_reservation.php, delete_reservation.php

  Microsoft OAuth:
    microsoft_login.php, microsoft_callback.php

  Ostatné:
    lib/auth.php          spoločné funkcie: session, db() (PDO), json_response(),
                          json_input(), require_login() (pre API),
                          require_login_page() (pre stránky), normalize_email(),
                          is_allowed_teacher_email()
    config.php            DB a OAuth nastavenia (vracia pole)
    schema.sql            tabuľky + ukážkové učebne

Stránky index.php a my_reservations.php sa po načítaní samé dotiahnu dáta cez
fetch() z JSON endpointov. PHP do nich vkladá iba meno prihláseného učiteľa.


2. DATABÁZA
-----------
Tri tabuľky (InnoDB, utf8mb4_unicode_ci):

  teachers      id, username (email, UNIQUE), full_name, password_hash,
                microsoft_id (UNIQUE, NULL), created_at
  classrooms    id, name (UNIQUE), note, created_at
  reservations  id, teacher_id (FK), classroom_id (FK), date, time_from,
                time_to, note, created_at
                - index (classroom_id, date) pre kontrolu konfliktov
                - index (teacher_id, date)
                - CHECK time_from < time_to
                - FK s ON DELETE RESTRICT (učiteľa ani učebňu s
                  rezerváciami nejde zmazať)

Učebne sa spravujú priamo v DB (tabuľka classrooms), v aplikácii na to nie je UI.


3. AUTENTIFIKÁCIA
-----------------
Identita je uložená v $_SESSION['teacher_id'] (session name z config.php).

Heslom (auth.php):
  - email sa znormalizuje a musí prejsť is_allowed_teacher_email()
    (doména @centrumnadania.sk)
  - registrácia: kontrola emailu, mena, zhody hesiel a duplicity, potom
    password_hash(PASSWORD_DEFAULT) a INSERT do teachers
  - login: SELECT podľa username, password_verify()
  - prihlásený používateľ sa z auth.php rovno presmeruje na index.php

Cez Microsoft (OAuth 2.0 authorization code flow + PKCE):
  1. microsoft_login.php vygeneruje `state` a PKCE code_verifier, uloží ich do
     session a presmeruje na login.microsoftonline.com (scope:
     openid email profile User.Read)
  2. microsoft_callback.php overí `state` (anti-CSRF), vymení `code` za tokeny
     cez cURL (POST na /oauth2/v2.0/token s client_secret a code_verifier)
  3. z id_tokenu sa base64-dekóduje payload (email / preferred_username,
     oid, name); podpis tokenu sa neoveruje, token ide priamo z token
     endpointu cez HTTPS
  4. email musí byť z povolenej domény
  5. učiteľ sa hľadá podľa username ALEBO microsoft_id; ak existuje a nemá
     uložené microsoft_id, doplní sa; ak neexistuje, vytvorí sa s náhodným
     heslom (cez heslo sa do takého účtu nedá prihlásiť)
  6. ak stĺpec microsoft_id v DB chýba (staršia schéma), kód to zachytí
     (try/catch na PDOException) a pokračuje bez neho

Autorizácia: rezervácie môže mazať iba ich autor (delete_reservation.php
porovnáva teacher_id s prihláseným učiteľom, inak 403). Prezerať kalendár
môže každý prihlásený.


4. TOK VYTVORENIA REZERVÁCIE
----------------------------
index.php -> POST create_reservation.php (JSON):
  { classroom_id, date, time_from, time_to, note }

Server:
  1. require_login(), parsovanie vstupu (POST aj JSON)
  2. validácia: classroom_id > 0, date vo formáte YYYY-MM-DD, časy HH:MM
     (alebo HH:MM:SS), time_from < time_to, oba časy zarovnané na 30 minút
     (400 pri chybe)
  3. načíta všetky rezervácie danej učebne v daný deň a porovná intervaly:
         konflikt ak NIE JE (existujuci_do <= novy_od  ALEBO
                             existujuci_od >= novy_do)
     takže rezervácie tesne za sebou (napr. 8:00-9:00 a 9:00-10:00) sú v poriadku
  4. pri konflikte vráti 409 s hláškou; frontend vtedy otvorí modál
     "Rezerváciu sa nepodarilo vytvoriť"
  5. inak INSERT a odpoveď {ok: true, id}

Všetky SQL dotazy sú pripravené príkazy (PDO prepare/execute). Výpisy do HTML
sa escapujú (htmlspecialchars v PHP, esc() v JS).


5. FRONTEND (index.php)
-----------------------
  - buildTimeOptions(): generuje časy 07:00 – 19:00 po 30 minútach
  - loadCalendar(): paralelne (Promise.all) stiahne get_classrooms.php
    a get_reservations.php?start_date&end_date pre zobrazený mesiac,
    výsledky nacachuje (cachedClassrooms, cachedReservations podľa dátumu)
  - farba dňa = pomer obsadených učební k všetkým učebniam v ten deň
    (počíta sa počet rôznych učební s aspoň jednou rezerváciou, nie hodiny):
    0 = zelená, čiastočne = svetločervená, všetky = červená
  - kliknutie na deň -> showDayDetail() vykreslí tabuľku z cache bez ďalšieho
    requestu
  - po úspešnej rezervácii sa zavolá loadCalendar() znova

my_reservations.php: načíta get_my_reservations.php, vykreslí tabuľku,
tlačidlo "Zrušiť" otvorí potvrdzovací modál a potom volá
delete_reservation.php?id=...


6. API
------
Všetky endpointy vyžadujú prihlásenie. Odpoveď má tvar {ok: bool, ...},
pri chybe {ok: false, error: "..."} a príslušný HTTP kód.

  GET  get_classrooms.php
       -> {ok, classrooms: [{id, name, note}]}

  GET  get_reservations.php?date=YYYY-MM-DD
  GET  get_reservations.php?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
       -> {ok, reservations: [{id, teacher_id, teacher_name, classroom_id,
           classroom_name, date, time_from, time_to, note}]}
       (400 pri neplatnom dátume)

  GET  get_my_reservations.php
       -> rezervácie prihláseného učiteľa, zoradené od najnovších

  POST create_reservation.php   (JSON alebo form)
       body: classroom_id, date, time_from, time_to, note
       -> {ok, id}            400 neplatný vstup, 409 konflikt

  GET|POST delete_reservation.php?id=  (id aj v JSON tele)
       -> {ok}                400 neplatné id, 403 cudzia rezervácia,
                              404 neexistuje


7. INŠTALÁCIA
-------------
  1) Databáza: v phpMyAdmin vytvor databázu `rezervacie`
     (utf8mb4_unicode_ci) a importuj schema.sql
  2) config.php: nastav db_host, db_name, db_user, db_pass. Pre Microsoft
     prihlásenie doplň microsoft_client_id, microsoft_client_secret,
     microsoft_tenant_id, microsoft_redirect_uri (appka musí byť zaregistrovaná
     v Entra ID a redirect URI sa musí zhodovať s microsoft_callback.php)
  3) Skopíruj priečinok do webrootu (XAMPP: htdocs/Rezervacie) a otvor
     http://localhost/Rezervacie/auth.php
  4) Požiadavky: PHP 8+ s PDO MySQL a cURL, MySQL 8.0.16+ / MariaDB 10.2+
     (kvôli CHECK constraintu)

Poznámka: config.php obsahuje prihlasovacie údaje, nedávaj ho do verejného
repozitára (pridaj do .gitignore a drž v ňom iba lokálne hodnoty).


8. ZNÁME OBMEDZENIA
-------------------
  - Kontrola konfliktov je len v PHP (SELECT + INSERT bez transakcie ani
    zámku), takže pri dvoch súbežných requestoch na ten istý termín sa môžu
    vytvoriť dve rezervácie. DB constraint na prekrývanie neexistuje.
  - Rozsah 07:00 – 19:00 vynucuje iba UI; backend akceptuje akýkoľvek čas
    zarovnaný na 30 minút. Minulé dátumy sa neblokujú.
  - Rušenie je cez GET (delete_reservation.php?id=), čo nie je ideálne pre
    operáciu, ktorá mení dáta (chýba CSRF ochrana).
  - Rezervácie sa nedajú upravovať, iba zrušiť a vytvoriť nanovo.
  - Správa učební a používateľov (admin) nie je, rieši sa priamo v DB.
  - V index.php ostali nepoužité premenné z predchádzajúcej verzie
    (dateLabel, tableBody) a zakomentovaný CSS.
