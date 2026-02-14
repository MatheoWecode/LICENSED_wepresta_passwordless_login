# Wepresta_Passwordless_Login

Module Wepresta_Passwordless_Login pour PrestaShop

## Installation

```bash
# Via console PrestaShop
bin/console prestashop:module install wepresta_passwordless_login

# Ou via DDEV
ddev exec bin/console prestashop:module install wepresta_passwordless_login
```

## Développement

### Structure

```
wepresta_passwordless_login/
├── wepresta_passwordless_login.php      # Fichier principal du module
├── config/
│   ├── services.yml       # Services Symfony
│   └── routes.yml         # Routes admin
├── controllers/
│   └── front/             # Contrôleurs front-office
├── src/
│   ├── Controller/        # Contrôleurs Symfony
│   ├── Entity/            # Entités Doctrine
│   ├── Repository/        # Repositories
│   └── Service/           # Services
├── sql/
│   ├── install.sql        # Tables à créer
│   └── uninstall.sql      # Tables à supprimer
├── views/
│   ├── css/               # Styles
│   ├── js/                # Scripts
│   └── templates/         # Templates Smarty
└── tests/                 # Tests PHPUnit
```

### Commandes

```bash
# Vider le cache
rm -rf var/cache/* && php bin/console cache:clear --no-warmup 2>/dev/null || true
# Tests
composer phpunit

# Analyse statique
composer phpstan
```

## Hooks enregistrés

- `displayHeader`
- `displayHome`
- `actionFrontControllerSetMedia`
- `actionAdminControllerSetMedia`

## Licence

MIT
