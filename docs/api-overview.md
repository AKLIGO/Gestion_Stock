# API Produits et Catégories

Ce document détaille les contrôleurs REST, les requêtes de validation et les relations Eloquent implémentés pour gérer les catégories, les produits et leurs images.

## 1. Contrôleur `CategorieController`

Fichier : `app/Http/Controllers/Products/CategorieController.php`

### Imports
- `App\Http\Controllers\Controller` : classe de base Laravel pour partager middleware et helpers.
- `App\Http\Requests\StoreCategoryRequest` / `UpdateCategoryRequest` : valident les données entrantes.
- `App\Models\Category` : modèle Eloquent représentant la table `categories`.
- `Illuminate\Http\JsonResponse` : type de retour explicite pour aider l’IDE et la lecture.

### Méthodes
1. `index()`
   - Charge toutes les catégories avec un compteur de produits (`withCount('products')`).
   - Retourne la liste paginée dans une réponse JSON.

2. `store(StoreCategoryRequest $request)`
   - Exécute les règles de `StoreCategoryRequest`.
   - Crée une catégorie avec `Category::create()`.
   - Renvoie les attributs créés avec un status HTTP 201.

3. `show(Category $category)`
   - Utilise l’injection de modèle de Laravel.
   - Charge la relation `products` pour renvoyer les produits de la catégorie.
   - Retourne le JSON sérialisé.

4. `update(UpdateCategoryRequest $request, Category $category)`
   - Valide les champs présents.
   - Met à jour la catégorie et renvoie la ressource actualisée.

5. `destroy(Category $category)`
   - Supprime la catégorie.
   - Retourne un statut 204 (pas de contenu) pour signaler la suppression.

## 2. Contrôleur `ProductController`

Fichier : `app/Http/Controllers/Products/ProductController.php`

### Imports
- `App\Models\Category` et `App\Models\Product` : manipulation des tables `categories` et `products`.
- `Illuminate\Support\Facades\DB` : fournit les transactions lors des créations/mises à jour.
- `Illuminate\Support\Facades\Storage` : gère la sauvegarde des fichiers dans `storage/app/public`.
- Requêtes `StoreProductRequest` et `UpdateProductRequest` pour la validation.

### Méthodes principales
1. `index()`
   - Charge produits + catégorie + images.
   - Ajoute le nombre d’images (`withCount('images')`).
   - Retourne les résultats paginés.

2. `store(StoreProductRequest $request)`
   - Valide toutes les données (prix, stock, fichiers, etc.).
   - Cherche la catégorie par son `name` et injecte son `id`.
   - Démarre une transaction pour créer le produit et lier les images.
   - Méthode privée `storeImageFiles()` : stocke chaque fichier, génère l’URL publique `/storage/...` et fixe un booléen `is_primary`.
   - Retourne la ressource créée avec les relations chargées (catégorie, images).

3. `show(Product $product)`
   - Charge catégorie et images du produit demandé.

4. `update(UpdateProductRequest $request, Product $product)`
   - Applique les validations partielles (champs optionnels).
   - Résout la catégorie par nom si fournie.
   - Transaction : met à jour la ligne, supprime les anciennes images sur disque + base, puis stocke les nouvelles si un tableau `images` est transmis.

5. `destroy(Product $product)`
   - Supprime le produit (les images liées sont supprimées en cascade via la contrainte de base de données).

### Méthodes utilitaires privées
- `storeImageFiles(array $images)`
  - Boucle sur chaque élément du tableau `images` soumis (contenant `file` et `is_primary`).
  - Utilise `store('product-images', 'public')` pour placer l’image dans `storage/app/public/product-images`.
  - Retourne un tableau normalisé (`path` public, `is_primary` booléen) pour `createMany`.

- `deleteProductStoredImages(Product $product)`
  - Parcourt les images existantes et supprime les fichiers physiques via `Storage::disk('public')->delete(...)`.

- `extractStoragePath(string $url)`
  - Convertit un lien public `/storage/...` en chemin interne relatif (`product-images/...`) avant suppression.

## 3. Contrôleur `ProductImageController`

Fichier : `app/Http/Controllers/Products/ProductImageController.php`

### Imports
- `ProductImage` pour manipuler les enregistrements.
- `Storage` pour stocker et supprimer les fichiers.
- Requêtes `StoreProductImageRequest` et `UpdateProductImageRequest` pour assurer la cohérence des données.

### Méthodes
1. `index()`
   - Liste paginée des images avec leur produit associé.

2. `store(StoreProductImageRequest $request)`
   - Valide le fichier et l’identifiant produit.
   - Sauvegarde le fichier sur disque, crée l’image en base et renvoie l’enregistrement avec la relation `product`.

3. `show(ProductImage $productImage)`
   - Renvoie l’image demandée avec son produit.

4. `update(UpdateProductImageRequest $request, ProductImage $productImage)`
   - Met à jour les champs (et remplace le fichier si un nouveau est fourni en supprimant l’ancien).

5. `destroy(ProductImage $productImage)`
   - Supprime l’enregistrement et retire le fichier du système.

### Méthodes utilitaires
- `extractStoragePath()` : identique à celle du `ProductController`, transforme l’URL publique en chemin interne.

## 4. Requêtes de validation (`FormRequest`)

### `StoreCategoryRequest` / `UpdateCategoryRequest`
- Autorisent toutes les requêtes (`authorize()` retourne `true`).
- Règles :
  - `name` obligatoire et unique lors de la création; optionnel mais unique lors de la mise à jour (ignore l’id courant).
  - `description` facultative.

```php
// StoreCategoryRequest
class StoreCategoryRequest extends FormRequest {
   public function rules(): array {
      return [
         'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
         'description' => ['nullable', 'string'],
      ];
   }
}

// UpdateCategoryRequest
class UpdateCategoryRequest extends FormRequest {
   public function rules(): array {
      $category = $this->route('category');
      $categoryId = is_object($category) ? $category->getKey() : $category;

      return [
         'name' => ['sometimes', 'string', 'max:255', 'unique:categories,name,' . $categoryId],
         'description' => ['nullable', 'string'],
      ];
   }
}
```

**Explications**
- `unique:categories,name` empêche la duplication du nom.
- `sometimes` signifie que le champ est valide uniquement s’il est présent dans la requête.
- `route('category')` récupère le modèle injecté pour ignorer son propre identifiant dans la contrainte d’unicité.

### `StoreProductRequest`
- Vérifie que `category_name` existe dans la table `categories`.
- `name`, `code_qr`, `price`, `stock` sont obligatoires. `code_qr` doit être unique.
- `images` est optionnel mais, s’il est présent, chaque entrée doit contenir :
  - `file` : fichier image (`mime` reconnu par Laravel) avec taille ≤ 5 Mo.
  - `is_primary` : booléen.

```php
class StoreProductRequest extends FormRequest {
   public function rules(): array {
      return [
         'category_name' => ['required', 'string', 'max:255', Rule::exists('categories', 'name')],
         'name' => ['required', 'string', 'max:255'],
         'code_qr' => ['required', 'string', 'max:255', 'unique:products,code_qr'],
         'price' => ['required', 'numeric', 'min:0'],
         'stock' => ['required', 'integer', 'min:0'],
         'description' => ['nullable', 'string'],
         'images' => ['sometimes', 'array'],
         'images.*.file' => ['required_with:images', 'file', 'image', 'max:5120'],
         'images.*.is_primary' => ['sometimes', 'boolean'],
      ];
   }
}
```

**Explications**
- `Rule::exists` s’assure que la catégorie demandée est présente.
- `required_with:images` impose le fichier pour chaque entrée lorsque le tableau existe.
- Les contraintes numériques (`min:0`, `numeric`, `integer`) sécurisent les montants.

### `UpdateProductRequest`
- Permet la modification partielle (`sometimes`).
- `code_qr` doit rester unique (ignore l’enregistrement actuel via l’id).
- Les fichiers sont optionnels mais suivent les mêmes contraintes que pour `store`.

```php
class UpdateProductRequest extends FormRequest {
   public function rules(): array {
      $product = $this->route('product');
      $productId = is_object($product) ? $product->getKey() : $product;

      return [
         'category_name' => ['sometimes', 'string', 'max:255', Rule::exists('categories', 'name')],
         'name' => ['sometimes', 'string', 'max:255'],
         'code_qr' => ['sometimes', 'string', 'max:255', 'unique:products,code_qr,' . $productId],
         'price' => ['sometimes', 'numeric', 'min:0'],
         'stock' => ['sometimes', 'integer', 'min:0'],
         'description' => ['nullable', 'string'],
         'images' => ['sometimes', 'array'],
         'images.*.file' => ['required_with:images', 'file', 'image', 'max:5120'],
         'images.*.is_primary' => ['sometimes', 'boolean'],
      ];
   }
}
```

**Explications**
- Le champ `code_qr` reste unique grâce au suffixe `,{$productId}` qui exclut le produit en cours.
- `required_with` garde la cohérence des sous-entrées du tableau `images`.

### `StoreProductImageRequest` et `UpdateProductImageRequest`
- `product_id` doit référencer un identifiant existant dans `products`.
- `image` est obligatoire à la création (fichier image ≤ 5 Mo) et optionnel à la mise à jour.
- `is_primary` booléen facultatif.

```php
class StoreProductImageRequest extends FormRequest {
   public function rules(): array {
      return [
         'product_id' => ['required', 'integer', 'exists:products,id'],
         'image' => ['required', 'file', 'image', 'max:5120'],
         'is_primary' => ['sometimes', 'boolean'],
      ];
   }
}

class UpdateProductImageRequest extends FormRequest {
   public function rules(): array {
      return [
         'product_id' => ['sometimes', 'integer', 'exists:products,id'],
         'image' => ['sometimes', 'file', 'image', 'max:5120'],
         'is_primary' => ['sometimes', 'boolean'],
      ];
   }
}
```

**Explications**
- `exists:products,id` garantit que les images se lient à un produit valide.
- `image` vérifie l’extension/MIME autorisée par PHP (jpeg, png, etc.).
- `max:5120` correspond à 5 Mo, limite standard pour des uploads raisonnables.

## 5. Relations Eloquent

### `Category`
- Méthode `products()` : `hasMany(Product::class)`.
  - Correspond à la clé étrangère `products.category_id` ajoutée dans la migration `create_products_table` (contrainte `foreignId('category_id')->constrained()->cascadeOnDelete()`).
  - Impose qu’une catégorie possède plusieurs produits; suppression de la catégorie supprime ses produits.
   - Migration : `Schema::create('products', function (Blueprint $table) { $table->foreignId('category_id')->constrained()->cascadeOnDelete(); ... });`

### `Product`
- `category()` : relation `belongsTo(Category::class)` basée sur la clé `category_id`.
- `images()` : relation `hasMany(ProductImage::class)` basée sur `product_images.product_id`.
   - Migration `create_product_images_table` définit la contrainte `foreignId('product_id')->constrained()->cascadeOnDelete()`
   - Ce lien signifie qu’un produit possède un ensemble d’images; suppression du produit supprime les images associées.

### `ProductImage`
- `product()` : `belongsTo(Product::class)`.
- La migration `create_product_images_table` définit la clé étrangère `product_id` avec suppression en cascade.
   - Exemple : `return $this->belongsTo(Product::class); // chaque image appartient à un seul produit`

## 6. Stockage des fichiers

- Disque utilisé : `public` (configuré par défaut dans `config/filesystems.php`), ce qui stocke les fichiers dans `storage/app/public` et expose un lien symbolique `public/storage` (commande `php artisan storage:link`).
- Les champs `path` conservent l’URL publique (`/storage/product-images/...`), facilitant l’affichage côté client sans divulguer le chemin interne.

## 7. Tests

- `tests/Feature/ProductApiTest.php` vérifie qu’un produit peut être créé via l’API avec `category_name` et des fichiers (`UploadedFile::fake()`), que les relations sont enregistrées et que les fichiers existent sur le disque simulé (`Storage::fake('public')`).

## 8. Relations entre Purchase, Product et User

### Purchase
- `Purchase` possède une relation `belongsTo` vers `User` et `Product`.
- Cela signifie qu’un achat est toujours lié à un utilisateur (acheteur) et à un produit spécifique.
- Exemple :
```php
public function user() { return $this->belongsTo(User::class); }
public function product() { return $this->belongsTo(Product::class); }
```

### User
- `User` possède une relation `hasMany` vers `Purchase`.
- Un utilisateur peut donc avoir plusieurs achats.
- Exemple :
```php
public function purchases() { return $this->hasMany(Purchase::class); }
```

### Product
- `Product` possède une relation `hasMany` vers `Purchase`.
- Un produit peut être acheté plusieurs fois.
- Exemple :
```php
public function purchases() { return $this->hasMany(Purchase::class); }
```

**Résumé :**
- Ces relations facilitent la récupération des achats d’un utilisateur ou d’un produit, et permettent de naviguer facilement entre les entités dans vos contrôleurs ou vues.
