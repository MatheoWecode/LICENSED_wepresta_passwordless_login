# Git Basics

Conventions Git pour ce projet.

## Workflow recommandé

Ce projet utilise le workflow **GitHub Flow** (simplifié) :

```
main ─────────────────────────────────────────────────────►
        │                               │
        └── feature/ma-fonctionnalite ──┘
                    (PR + merge)
```

1. Créer une branche depuis `main`
2. Développer et commiter
3. Ouvrir une Pull Request
4. Review et CI
5. Merge dans `main`

---

## Branches

### Nommage

| Préfixe | Usage |
|---------|-------|
| `feature/` | Nouvelle fonctionnalité |
| `fix/` | Correction de bug |
| `refactor/` | Refactoring sans changement fonctionnel |
| `docs/` | Documentation |
| `chore/` | Maintenance, dépendances |

### Exemples

```bash
# Créer une branche
git checkout -b feature/add-export-csv
git checkout -b fix/cart-calculation
git checkout -b refactor/clean-architecture
git checkout -b docs/update-readme
git checkout -b chore/update-dependencies
```

---

## Commits

### Format conventionnel

```
<type>(<scope>): <description>

[body optionnel]

[footer optionnel]
```

### Types

| Type | Description |
|------|-------------|
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `docs` | Documentation |
| `style` | Formatage (pas de changement de code) |
| `refactor` | Refactoring |
| `test` | Ajout/modification de tests |
| `chore` | Maintenance |

### Exemples

```bash
# Fonctionnalité
git commit -m "feat(cart): add quantity validation"

# Correction
git commit -m "fix(payment): handle timeout properly"

# Refactoring
git commit -m "refactor(service): extract calculation logic"

# Documentation
git commit -m "docs: update installation guide"

# Tests
git commit -m "test(service): add unit tests for ItemService"
```

---

## Workflow quotidien

### Démarrer une fonctionnalité

```bash
# 1. Partir de main à jour
git checkout main
git pull origin main

# 2. Créer la branche
git checkout -b feature/new-feature

# 3. Développer...

# 4. Vérifier la qualité
composer test

# 5. Commiter
git add .
git commit -m "feat: add new feature"

# 6. Pusher
git push origin feature/new-feature

# 7. Ouvrir une PR sur GitHub
```

### Corriger un bug

```bash
git checkout main
git pull origin main
git checkout -b fix/bug-description

# Corriger...

composer test
git add .
git commit -m "fix: resolve bug description"
git push origin fix/bug-description
```

---

## Pull Requests

### Checklist avant PR

- [ ] Branche à jour avec `main`
- [ ] `composer test` passe
- [ ] Assets compilés (`npm run build`)
- [ ] Pas de fichiers indésirables (`.DS_Store`, logs)

### Template PR

```markdown
## Description

Description claire du changement.

## Type de changement

- [ ] Nouvelle fonctionnalité
- [ ] Correction de bug
- [ ] Refactoring
- [ ] Documentation

## Checklist

- [ ] Tests ajoutés/mis à jour
- [ ] Documentation mise à jour
- [ ] `composer test` passe
```

---

## Résoudre les conflits

### Mise à jour de la branche

```bash
# Récupérer les derniers changements de main
git checkout main
git pull origin main

# Retourner sur votre branche
git checkout feature/ma-fonctionnalite

# Rebaser sur main
git rebase main

# Résoudre les conflits si nécessaire
# Éditer les fichiers en conflit
git add .
git rebase --continue

# Pusher (force nécessaire après rebase)
git push origin feature/ma-fonctionnalite --force-with-lease
```

---

## Fichiers ignorés

Le `.gitignore` exclut :

```gitignore
# Dépendances
/vendor/
/node_modules/

# Build
/views/dist/

# Cache
/var/

# IDE
.idea/
.vscode/
*.swp

# OS
.DS_Store
Thumbs.db

# Logs
*.log

# Secrets
.env.local
```

---

## Bonnes pratiques

### Commits atomiques

```bash
# ✅ Un commit = un changement logique
git commit -m "feat: add export button"
git commit -m "feat: implement export logic"
git commit -m "test: add export tests"

# ❌ Un commit fourre-tout
git commit -m "add export, fix bug, update styles, etc."
```

### Messages descriptifs

```bash
# ✅ Clair et précis
git commit -m "fix(cart): prevent negative quantities"

# ❌ Vague
git commit -m "fix bug"
git commit -m "update"
```

### Historique propre

```bash
# Avant de pusher, nettoyer l'historique
git rebase -i HEAD~3  # Squash les petits commits
```

---

**Prochaine étape** : [GitHub Actions](./github-actions.md)

