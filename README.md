# Soft Drink Web Organizer

A web application for managing preferences and information related to non-alcoholic drinks, such as teas, dairy drinks, juices, syrups, and more.

## Project Structure

```text
soft-drink-web-organizer/
│
├── config/
│   └── Database.php          # database connection configuration
│
├── models/                   # domain models / entities
│   ├── Product.php
│   ├── User.php
│   ├── Category.php
│   ├── ShoppingList.php
│   └── Allergen.php
│
├── repositories/             # data access layer
│   ├── ProductRepository.php
│   ├── UserRepository.php
│   └── ShoppingListRepository.php
│
├── services/                 # business logic
│   ├── ProductService.php
│   ├── UserService.php
│   ├── StatisticsService.php
│   └── OpenFoodFactsService.php
│
├── controllers/              # request handling logic
│   ├── ProductController.php
│   ├── UserController.php
│   └── AdminController.php
│
├── api/                      # AJAX endpoints returning JSON/XML
│   ├── products.php
│   ├── users.php
│   ├── stats.php
│   └── rss.php
│
├── templates/                # reusable UI components
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
│
├── pages/                    # application pages
│   ├── home.php
│   ├── catalog.php
│   ├── product.php
│   └── shopping-list.php
│
├── admin/                    # admin module
│   ├── index.php
│   ├── products.php
│   └── users.php
│
├── public/                   # static assets
│   ├── css/
│   ├── js/
│   └── images/
│
├── db/
│   ├── schema.sql
│   └── seed.sql
│
├── .gitignore
└── index.php                 # application entry point
```
