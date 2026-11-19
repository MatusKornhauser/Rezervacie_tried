1) Databáza
- V phpMyAdmin vytvor databázu: rezervacie (utf8mb4_unicode_ci)
- Importuj súbor schema.sql

2) Nastavenie pripojenia
- Otvor config.php a nastav:
  db_host, db_name, db_user, db_pass

3) Spustenie
- Skopíruj priečinok Rezervacie do webroot (napr. XAMPP: htdocs/Rezervacie)
- Otvor v prehliadači:
  http://localhost/Rezervacie/register.php

4) Použitie
- Registruj učiteľa emailom v tvare name.surname@centrumnadania.sk a nastav heslo
- Na index.php:
  - vyber dátum
  - vyber učebňu + čas od/do (30-min bloky)
  - rezervuj
- Konflikty sú blokované (PHP + SQL kontrola)
- Moje rezervácie: my_reservations.php (zrušiť môžeš iba svoje)

5) Endpointy (JSON)
- get_classrooms.php
- get_reservations.php?date=YYYY-MM-DD
- create_reservation.php (POST JSON alebo form)
- delete_reservation.php?id=
