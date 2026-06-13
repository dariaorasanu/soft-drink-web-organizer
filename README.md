# SOr — Soft Drink Web Organizer

SOr este o aplicație web pentru organizarea băuturilor non-alcoolice. Aplicația permite utilizatorilor să exploreze un catalog de produse, să vadă detalii despre băuturi, să adauge produse la favorite, să ofere ratinguri, să gestioneze liste de cumpărături și să urmărească statistici despre produse.

Proiectul folosește PHP, PostgreSQL, HTML, CSS și JavaScript, fiind organizat pe straturi: pagini, endpointuri API, controllere, servicii și repository-uri. Interfața este responsive și poate fi folosită atât pe desktop, cât și pe mobil.

## Funcționalități principale

### Autentificare și roluri

- înregistrare cont nou;
- autentificare și logout;
- autentificare pe bază de JWT semnat cu HS256;
- token salvat în cookie `HttpOnly` și `SameSite=Lax`;
- roluri separate pentru utilizator obișnuit și administrator;
- protejarea paginilor private și a zonei de administrare prin `AuthGuard`.

### Catalog de produse

- afișarea produselor din baza de date;
- căutare după text;
- filtrare după categorie, sezon și regiune;
- carduri de produs cu imagine, descriere, preț, categorie și status de favorit;
- actualizare dinamică prin JavaScript și endpointuri API;
- clasament pentru produsele populare.

### Pagină produs

- detalii despre produs: nume, brand, descriere, preț, volum și ingrediente;
- afișare alergeni, categorii, sezoane, regiuni și localuri asociate;
- tabel nutrițional;
- integrare cu Open Food Facts pentru completarea unor date nutriționale;
- sistem de rating și review;
- adăugare sau eliminare din favorite;
- incrementarea vizualizărilor pentru statistici și RSS.

### Liste de cumpărături

- creare și administrare listă de cumpărături;
- adăugare produse în listă;
- bifare produse cumpărate;
- mod de cumpărături adaptat pentru utilizare rapidă;
- liste partajate prin link public;
- sincronizare periodică pentru lista partajată;
- interacțiuni mobile, inclusiv swipe pentru bifarea produselor.

### Statistici și exporturi

- statistici despre produse populare;
- produse favorite și produse cu rating ridicat;
- export CSV;
- export JSON;
- export SVG;
- feed RSS pentru clasamentul produselor populare.

### Administrare

- panou de administrare pentru produse și utilizatori;
- operații CRUD pentru produse;
- administrarea utilizatorilor;
- import și export de date;
- acces permis doar utilizatorilor cu rol de administrator.

## Tehnologii folosite

- PHP 8.x;
- PostgreSQL;
- PDO pentru acces la baza de date;
- HTML5;
- CSS3;
- JavaScript vanilla;
- JWT cu HMAC-SHA256;
- Docker și Railway pentru deploy;
- Stylelint pentru validarea CSS.

## Structura proiectului

```text
soft-drink-web-organizer/
├── admin/                  # pagini pentru administrare
├── api/                    # endpointuri folosite de interfață
├── config/                 # bootstrap și configurare bază de date
├── controllers/            # logica HTTP
├── db/                     # migrări și seed-uri
├── middleware/             # AuthGuard
├── models/                 # modele de domeniu
├── pages/                  # pagini principale ale aplicației
├── public/
│   ├── css/                # stiluri
│   └── js/                 # scripturi frontend
├── repositories/           # acces la date
├── service/                # logică de business
├── templates/              # componente reutilizabile de interfață
├── Dockerfile
├── railway.json
├── package.json
└── index.php
```

## Arhitectură

Aplicația este împărțită în trei zone principale: interfața web, backend-ul PHP și baza de date PostgreSQL. Interfața comunică prin `fetch()` cu endpointurile din `api/`, iar acestea trimit cererile mai departe către controllere, servicii și repository-uri.


## Modelul datelor

Datele sunt păstrate în PostgreSQL și sunt accesate prin repository-uri. Modelul include entități pentru utilizatori, produse, categorii, alergeni, favorite, ratinguri și liste de cumpărături.

Principalele zone de date sunt:

- utilizatori și roluri;
- produse și categorii;
- alergeni și restricții alimentare;
- favorite;
- ratinguri;
- liste de cumpărături și produse asociate;
- statistici generate pe baza interacțiunilor utilizatorilor.

## API-uri și formate de date

Aplicația expune endpointuri interne pentru acțiunile folosite în interfață.

Exemple:

- `api/users.php` — login, register, logout;
- `api/product.php` — listare, căutare, detalii produs, favorite, ratinguri;
- `api/shopping-list.php` — operații pentru liste de cumpărături;
- `api/stats.php` — statistici și exporturi;
- `api/rss.php` — feed RSS;
- `api/admin.php` — operații pentru zona de administrare.

Formatele folosite sunt:

- JSON pentru răspunsurile API;
- CSV pentru export de date;
- SVG pentru reprezentări grafice exportabile;
- RSS pentru clasamentul produselor populare.

## Securitate

Aplicația include mai multe măsuri de securitate:

- autentificare cu JWT;
- semnare token cu HMAC-SHA256;
- cookie `HttpOnly`;
- cookie `SameSite=Lax`;
- cookie `Secure` în producție;
- verificare rol administrator prin `AuthGuard`;
- interogări SQL cu prepared statements;
- escaparea datelor afișate cu `htmlspecialchars`;
- separarea logicii de acces la date în repository-uri.

## Design și interfață

Interfața folosește un design întunecat, cu accente verzi și roz, potrivit pentru o aplicație modernă de organizare și explorare produse. Cardurile, badge-urile, butoanele și navbar-ul sunt reutilizate pentru a păstra consistența vizuală.

### Paletă principală

| Rol | Culoare |
| --- | --- |
| Background principal | `#171817` |
| Background carduri | `#252824` |
| Navbar | `#262826` |
| Accent principal | `#8df0c0` |
| Accent secundar | `#f72585` |
| Text principal | `#f4f1ea` |
| Text secundar | `#bcb5aa` |

### Fonturi

Aplicația folosește:

- `Nunito` pentru text, butoane și label-uri;
- `Playfair Display` pentru titluri mari și brand.

## Instalare și rulare locală

### 1. Clonarea proiectului

```bash
git clone <repo-url>
cd soft-drink-web-organizer
```

### 2. Instalarea dependențelor frontend pentru validarea CSS

```bash
npm install
```

### 3. Configurarea bazei de date

Se creează o bază de date PostgreSQL și se configurează variabilele de conectare în fișierul de mediu folosit de aplicație.

Exemplu de variabile:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=soft_drink_web_organizer
DB_USER=postgres
DB_PASSWORD=parola
```

### 4. Rularea migrărilor și seed-urilor

```bash
php db/migrate.php
```

Seed-ul inițial include produse, categorii, alergeni și utilizatori de test.

### 5. Pornirea serverului local

```bash
php -S localhost:8000
```

Aplicația poate fi accesată la:

```text
http://localhost:8000
```

## Conturi de test

```text
Admin: admin@sor.ro / parola123
User:  user@sor.ro  / parola123
```

## Validare CSS

Pentru verificarea fișierelor CSS se poate rula:

```bash
npm run lint:css
```

## Deploy

Aplicația este pregătită pentru deploy pe Railway, folosind PostgreSQL ca bază de date și configurare prin variabile de mediu. Fișierele `Dockerfile` și `railway.json` sunt incluse pentru rularea aplicației în producție.

## Documentație

Documentația tehnică este disponibilă în folderul `docs/`:

- `docs/raport-scholarly.html` — raportul tehnic în format Scholarly HTML;

