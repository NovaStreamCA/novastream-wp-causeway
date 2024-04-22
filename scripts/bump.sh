#!/bin/bash
# Script to bump version numbers and tag it in Git.
# @author Matt Lewis
# @date 2024-04-17

set -e

get_version() {
    jq -r '.version' "$COMPOSER_JSON_PATH"
}

bump_version() {
    CURRENT_VERSION=$(get_version)
    MAJOR=$(echo "$CURRENT_VERSION" | cut -d. -f1)
    MINOR=$(echo "$CURRENT_VERSION" | cut -d. -f2)
    REVISION=$(echo "$CURRENT_VERSION" | cut -d. -f3)

    case "$1" in
        --major)
            ((MAJOR++))
            MINOR=0
            REVISION=0
            ;;
        --minor)
            ((MINOR++))
            REVISION=0
            ;;
        --rev)
            ((REVISION++))
            ;;
        *)
            echo "Invalid argument. Usage: $0 --major/--minor/--rev"
            exit 1
            ;;
    esac

    NEW_VERSION="$MAJOR.$MINOR.$REVISION"
    echo "$NEW_VERSION"
}

update_files() {
    NEW_VERSION=$1
    jq ".version = \"$NEW_VERSION\"" "$COMPOSER_JSON_PATH" > "$COMPOSER_JSON_PATH.tmp" && mv "$COMPOSER_JSON_PATH.tmp" "$COMPOSER_JSON_PATH"
    sed -i "s/\$version = '[0-9.]*';/\$version = '$NEW_VERSION';/" "$PHP_PATH"
}

COMPOSER_JSON_PATH="composer.json"
PHP_PATH="novastream-wp-causeway.php"


if [ ! -f "$COMPOSER_JSON_PATH" ]; then
echo $(dirname "$0")
    echo "Error: composer.json cannot be found in the $0"
    exit 1
fi

if [ ! -f "$PHP_PATH" ]; then
    echo "Error: novastream-wp-causeway.php cannot be found in the current directory"
    exit 1
fi

case "$1" in
    --major|--minor|--rev)
        NEW_VERSION=$(bump_version "$1")
        update_files "$NEW_VERSION"

        # Add and commit changes to Git
        git add .
        git commit -m "Bumped version to $NEW_VERSION"

        # Tag the commit
        git tag -a "v$NEW_VERSION" -m "Version $NEW_VERSION"

        git push origin master --tags

        echo "Version bumped to $NEW_VERSION"
        ;;
    *)
        echo "Invalid argument. Usage: $0 --major/--minor/--rev"
        exit 1
        ;;
esac
