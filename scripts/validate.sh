#!/bin/bash
# =============================================================================
# Script de validation du module PrestaShop
# Usage: ddev exec bash modules/modulestarter/scripts/validate.sh
# Ou: wedev ps module validate modulestarter
# =============================================================================

# Auto-détection du nom du module depuis le chemin
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_PATH="$(dirname "$SCRIPT_DIR")"
MODULE_NAME="$(basename "$MODULE_PATH")"
ERRORS=0
WARNINGS=0

echo "🔍 Validation du module $MODULE_NAME..."
echo "=============================================="

# -----------------------------------------------------------------------------
# 1. Vérifier la syntaxe PHP
# -----------------------------------------------------------------------------
echo ""
echo "📝 1. Vérification syntaxe PHP..."
PHP_ERRORS=0
for file in $(find $MODULE_PATH/src -name "*.php" 2>/dev/null); do
    result=$(php -l "$file" 2>&1)
    if [[ $? -ne 0 ]]; then
        echo "❌ $(basename $file)"
        ((PHP_ERRORS++))
        ((ERRORS++))
    fi
done
if [[ $PHP_ERRORS -eq 0 ]]; then
    echo "✅ Syntaxe PHP OK"
fi

# -----------------------------------------------------------------------------
# 2. Vérifier le fichier principal du module
# -----------------------------------------------------------------------------
echo ""
echo "📦 2. Vérification fichier principal..."
if [[ -f "$MODULE_PATH/$MODULE_NAME.php" ]]; then
    php -l "$MODULE_PATH/$MODULE_NAME.php" >/dev/null 2>&1
    if [[ $? -eq 0 ]]; then
        echo "✅ $MODULE_NAME.php OK"
    else
        echo "❌ $MODULE_NAME.php a des erreurs de syntaxe"
        ((ERRORS++))
    fi
else
    echo "❌ Fichier $MODULE_NAME.php non trouvé"
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# 3. Vérifier la configuration YAML
# -----------------------------------------------------------------------------
echo ""
echo "⚙️ 3. Vérification fichiers YAML..."
for yaml_file in "$MODULE_PATH/config/services.yml" "$MODULE_PATH/config/routes.yml"; do
    if [[ -f "$yaml_file" ]]; then
        echo "✅ $(basename $yaml_file) présent"
    else
        echo "⚠️  $(basename $yaml_file) manquant"
        ((WARNINGS++))
    fi
done

# -----------------------------------------------------------------------------
# 4. Vérifier les routes Symfony
# -----------------------------------------------------------------------------
echo ""
echo "🛤️ 4. Vérification des routes..."
ROUTES=$(php bin/console debug:router 2>/dev/null | grep -c "$MODULE_NAME" || echo "0")
if [[ "$ROUTES" -gt 0 ]]; then
    echo "✅ $ROUTES routes trouvées"
else
    echo "⚠️  Aucune route trouvée - vider le cache"
    ((WARNINGS++))
fi

# -----------------------------------------------------------------------------
# 5. Vérifier les services Symfony
# -----------------------------------------------------------------------------
echo ""
echo "🔧 5. Vérification des services..."
SERVICES=$(php bin/console debug:container 2>/dev/null | grep -ci "$MODULE_NAME" || echo "0")
if [[ "$SERVICES" -gt 0 ]]; then
    echo "✅ Services enregistrés"
else
    echo "⚠️  Aucun service trouvé"
    ((WARNINGS++))
fi

# -----------------------------------------------------------------------------
# 6. Vérifier les dépendances composer
# -----------------------------------------------------------------------------
echo ""
echo "📚 6. Vérification autoload..."
if [[ -f "$MODULE_PATH/vendor/autoload.php" ]]; then
    echo "✅ Autoload présent"
else
    echo "❌ vendor/autoload.php manquant - exécuter: composer install"
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# 7. Vérifier AdminSecurity (PS9 = Attributes PHP 8)
# -----------------------------------------------------------------------------
echo ""
echo "🔒 7. Vérification AdminSecurity..."
CONTROLLER_DIR="$MODULE_PATH/src/Presentation/Controller"
if [[ -d "$CONTROLLER_DIR" ]]; then
    LEGACY_ANNOTATIONS=$(grep -r "@AdminSecurity" "$CONTROLLER_DIR" 2>/dev/null | wc -l | tr -d ' ')
    PHP8_ATTRIBUTES=$(grep -r "#\[AdminSecurity" "$CONTROLLER_DIR" 2>/dev/null | wc -l | tr -d ' ')
    
    if [[ "$LEGACY_ANNOTATIONS" -gt 0 ]]; then
        echo "❌ $LEGACY_ANNOTATIONS @AdminSecurity (annotations legacy)"
        echo "   Migrer vers #[AdminSecurity] (Attributes PHP 8)"
        ((ERRORS++))
    fi
    
    if [[ "$PHP8_ATTRIBUTES" -gt 0 ]]; then
        echo "✅ $PHP8_ATTRIBUTES #[AdminSecurity] (Attributes PHP 8)"
        
        # Vérifier les routes invalides
        BAD_ROUTES=$(grep -r "#\[AdminSecurity" "$CONTROLLER_DIR" 2>/dev/null | grep -E "admin_dashboard|admin_domain" | wc -l | tr -d ' ')
        if [[ "$BAD_ROUTES" -gt 0 ]]; then
            echo "❌ Routes invalides détectées (admin_dashboard/admin_domain n'existent pas en PS9)"
            ((ERRORS++))
        fi
        
        # Vérifier redirectRoute
        MISSING_REDIRECT=$(grep -r "#\[AdminSecurity" "$CONTROLLER_DIR" 2>/dev/null | grep -v "redirectRoute" | wc -l | tr -d ' ')
        if [[ "$MISSING_REDIRECT" -gt 0 ]]; then
            echo "⚠️  $MISSING_REDIRECT AdminSecurity sans redirectRoute"
            ((WARNINGS++))
        fi
    fi
else
    echo "- Pas de contrôleurs Symfony"
fi

# -----------------------------------------------------------------------------
# 8. Test warmup cache
# -----------------------------------------------------------------------------
echo ""
echo "🔄 8. Test warmup cache..."
CACHE_RESULT=$(php bin/console cache:warmup 2>&1)
if [[ $? -eq 0 ]]; then
    echo "✅ Cache warmup OK"
else
    echo "❌ Erreur lors du warmup cache"
    echo "$CACHE_RESULT" | grep -iE "error|exception" | head -3
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# Résumé
# -----------------------------------------------------------------------------
echo ""
echo "=============================================="
if [[ $ERRORS -eq 0 && $WARNINGS -eq 0 ]]; then
    echo "✅ VALIDATION RÉUSSIE - Aucune erreur"
elif [[ $ERRORS -eq 0 ]]; then
    echo "⚠️  VALIDATION OK avec $WARNINGS avertissement(s)"
else
    echo "❌ VALIDATION ÉCHOUÉE - $ERRORS erreur(s), $WARNINGS avertissement(s)"
fi
echo "=============================================="

exit $ERRORS

