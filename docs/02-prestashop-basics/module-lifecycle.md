# Cycle de vie d'un module

Comprendre comment PrestaShop charge, installe et exécute votre module.

## États d'un module

Un module peut être dans différents états :

```
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│   Présent     →    Installé    →    Activé                  │
│   (fichiers)      (BDD + hooks)    (exécuté)                │
│                                                              │
│       ↓              ↓               ↓                       │
│   Absent       Désinstallé      Désactivé                   │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

| État | Description |
|------|-------------|
| **Présent** | Fichiers dans `modules/`, mais pas installé |
| **Installé** | Enregistré en BDD, hooks configurés |
| **Activé** | Exécuté à chaque requête (hooks appelés) |
| **Désactivé** | Installé mais non exécuté |
| **Désinstallé** | Configuration et tables supprimées |

---

## Installation d'un module

Lors de l'installation (`bin/console prestashop:module install xxx`) :

### 1. Vérifications

```
✓ Fichier principal existe (xxx.php)
✓ Classe hérite de Module
✓ Version PHP compatible
✓ Version PrestaShop compatible
```

### 2. Méthode `install()` appelée

```php
public function install(): bool
{
    return parent::install()
        && $this->registerHook($this->getHooks())
        && $this->installConfiguration()
        && $this->installDatabase();
}
```

### 3. Actions effectuées

1. **Insertion en BDD** : table `ps_module`
2. **Enregistrement des hooks** : table `ps_hook_module`
3. **Configuration initiale** : table `ps_configuration`
4. **Création des tables** : tables personnalisées
5. **Création des onglets admin** : table `ps_tab`

---

## Désinstallation

Lors de la désinstallation :

```php
public function uninstall(): bool
{
    return parent::uninstall()
        && $this->uninstallConfiguration()
        && $this->uninstallDatabase();
}
```

### Actions effectuées

1. Suppression de `ps_module`
2. Suppression des hooks de `ps_hook_module`
3. Suppression de la configuration
4. Suppression des tables personnalisées
5. Suppression des onglets admin

> ⚠️ **Attention** : La désinstallation peut supprimer des données utilisateur. Proposez une option pour conserver les données.

---

## Activation / Désactivation

Différence avec install/uninstall :
- **Désactiver** : Le module reste installé mais les hooks ne sont plus appelés
- **Désinstaller** : Supprime toute trace du module

```bash
# Désactiver
bin/console prestashop:module disable monmodule

# Réactiver
bin/console prestashop:module enable monmodule

# Réinitialiser (uninstall + install)
bin/console prestashop:module reset monmodule
```

---

## Ordre de chargement

À chaque requête, PrestaShop :

1. **Initialise le Context** (shop, langue, devise)
2. **Charge les modules actifs** par ordre de position
3. **Exécute le contrôleur** (front ou admin)
4. **Appelle les hooks** aux points d'accroche
5. **Rend la vue** (Smarty ou Twig)

### Position des modules

L'ordre d'exécution des hooks dépend de la **position** :

```sql
SELECT m.name, hm.position
FROM ps_hook_module hm
JOIN ps_module m ON m.id_module = hm.id_module
JOIN ps_hook h ON h.id_hook = hm.id_hook
WHERE h.name = 'displayHome'
ORDER BY hm.position;
```

Modifiable via Back-office → Design → Positions.

---

## Mise à jour d'un module

Pour mettre à jour un module installé :

### 1. Créer un fichier d'upgrade

```
upgrade/
└── upgrade-1.1.0.php
```

```php
function upgrade_module_1_1_0($module): bool
{
    // Migration vers 1.1.0
    return Db::getInstance()->execute("ALTER TABLE ...");
}
```

### 2. Modifier la version

```php
// Dans le constructeur du module
$this->version = '1.1.0';
```

### 3. PrestaShop détecte le changement

Lors du prochain accès au back-office, PrestaShop :
1. Compare la version en BDD avec celle du fichier
2. Exécute les scripts d'upgrade manquants
3. Met à jour la version en BDD

---

## Bonnes pratiques

### Installation

- ✅ Toujours appeler `parent::install()`
- ✅ Utiliser des transactions pour les opérations BDD
- ✅ Prévoir un rollback en cas d'erreur
- ✅ Valider les prérequis avant installation

### Désinstallation

- ✅ Proposer de conserver les données
- ✅ Nettoyer toutes les ressources créées
- ✅ Supprimer les fichiers de cache

### Upgrade

- ✅ Un fichier par version
- ✅ Scripts idempotents (exécutables plusieurs fois)
- ✅ Backup avant modification de données

---

<details>
<summary>💡 Déboguer l'installation d'un module</summary>

Si l'installation échoue silencieusement :

```php
public function install(): bool
{
    try {
        if (!parent::install()) {
            throw new Exception('parent::install failed');
        }
        // ...
    } catch (Exception $e) {
        PrestaShopLogger::addLog($e->getMessage(), 3);
        $this->_errors[] = $e->getMessage();
        return false;
    }
}
```

Vérifiez ensuite `var/logs/` et l'onglet **Erreurs** du module.

</details>

---

**Prochaine étape** : [Système de Hooks](./hooks-explained.md)

