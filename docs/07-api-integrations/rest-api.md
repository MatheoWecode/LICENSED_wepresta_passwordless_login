# API REST

> Référence technique détaillée : [.cursor/rules/010-module-api.mdc](../../.cursor/rules/010-module-api.mdc)

Ce module peut exposer une API REST pour les intégrations externes.

## Activer l'API

```php
// Dans la configuration
Configuration::updateValue('MONMODULE_API_ENABLED', true);
```

---

## Endpoints disponibles

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/monmodule/health` | Status de l'API |
| GET | `/api/monmodule/items` | Liste des items |
| GET | `/api/monmodule/items/{id}` | Détail d'un item |
| POST | `/api/monmodule/items` | Créer un item |
| PUT | `/api/monmodule/items/{id}` | Modifier un item |
| DELETE | `/api/monmodule/items/{id}` | Supprimer un item |

---

## Authentification

### API Key

L'API utilise une clé d'authentification :

```bash
# Header requis
Authorization: Bearer YOUR_API_KEY
```

### Obtenir une clé

1. Back-office → Module → Configuration
2. Section "API"
3. Générer une nouvelle clé

### Exemple de requête

```bash
curl -X GET "https://shop.local/api/monmodule/items" \
  -H "Authorization: Bearer sk_live_xxxxx" \
  -H "Content-Type: application/json"
```

---

## Format des réponses

### Succès

```json
{
    "success": true,
    "data": {
        "items": [
            {"id": 1, "name": "Item A", "active": true},
            {"id": 2, "name": "Item B", "active": true}
        ],
        "total": 2
    },
    "meta": {
        "page": 1,
        "per_page": 20,
        "total_pages": 1
    }
}
```

### Erreur

```json
{
    "success": false,
    "error": {
        "code": "ITEM_NOT_FOUND",
        "message": "Item with ID 999 not found"
    }
}
```

### Codes HTTP

| Code | Signification |
|------|---------------|
| 200 | Succès |
| 201 | Créé |
| 400 | Requête invalide |
| 401 | Non authentifié |
| 403 | Non autorisé |
| 404 | Ressource non trouvée |
| 422 | Validation échouée |
| 500 | Erreur serveur |

---

## Exemples d'utilisation

### Lister les items

```bash
curl -X GET "https://shop.local/api/monmodule/items?active=1&page=1" \
  -H "Authorization: Bearer sk_live_xxxxx"
```

Réponse :
```json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 1,
                "name": "Item A",
                "description": "Description",
                "active": true,
                "position": 0,
                "created_at": "2024-12-22T10:30:00+01:00"
            }
        ],
        "total": 1
    }
}
```

### Créer un item

```bash
curl -X POST "https://shop.local/api/monmodule/items" \
  -H "Authorization: Bearer sk_live_xxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Nouvel item",
    "description": "Description",
    "active": true
  }'
```

Réponse :
```json
{
    "success": true,
    "data": {
        "id": 42,
        "name": "Nouvel item",
        "description": "Description",
        "active": true
    },
    "message": "Item created successfully"
}
```

### Modifier un item

```bash
curl -X PUT "https://shop.local/api/monmodule/items/42" \
  -H "Authorization: Bearer sk_live_xxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Item modifié",
    "active": false
  }'
```

### Supprimer un item

```bash
curl -X DELETE "https://shop.local/api/monmodule/items/42" \
  -H "Authorization: Bearer sk_live_xxxxx"
```

---

## Pagination

### Paramètres

| Paramètre | Défaut | Description |
|-----------|--------|-------------|
| `page` | 1 | Numéro de page |
| `per_page` | 20 | Items par page (max 100) |

### Exemple

```bash
curl "https://shop.local/api/monmodule/items?page=2&per_page=50"
```

### Réponse avec pagination

```json
{
    "success": true,
    "data": { ... },
    "meta": {
        "page": 2,
        "per_page": 50,
        "total_items": 150,
        "total_pages": 3
    },
    "links": {
        "first": "/api/monmodule/items?page=1",
        "prev": "/api/monmodule/items?page=1",
        "next": "/api/monmodule/items?page=3",
        "last": "/api/monmodule/items?page=3"
    }
}
```

---

## Filtres

| Paramètre | Type | Description |
|-----------|------|-------------|
| `active` | bool | Filtrer par statut |
| `search` | string | Recherche par nom |
| `created_after` | date | Créé après (ISO 8601) |
| `created_before` | date | Créé avant |

### Exemple

```bash
curl "https://shop.local/api/monmodule/items?active=1&search=test&created_after=2024-01-01"
```

---

## Rate Limiting

L'API limite les requêtes pour protéger le serveur :

| Limite | Valeur |
|--------|--------|
| Requêtes/minute | 60 |
| Requêtes/heure | 1000 |

### Headers de réponse

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1703250000
```

### Erreur 429

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Too many requests. Please retry after 60 seconds."
    }
}
```

---

## CORS

L'API supporte les requêtes cross-origin :

```php
// Headers ajoutés automatiquement
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Authorization, Content-Type
```

---

## Tester l'API

### Health check

```bash
curl "https://shop.local/api/monmodule/health"
```

```json
{
    "success": true,
    "status": "ok",
    "module": "monmodule",
    "version": "1.0.0",
    "api_enabled": true,
    "timestamp": "2024-12-22T10:30:00+01:00"
}
```

### Avec Postman

1. Créez une collection "Module API"
2. Ajoutez le header `Authorization` dans les settings
3. Importez les endpoints

---

**Prochaine étape** : [Webhooks](./webhooks.md)

