# Somewhere API Documentation

Documentation de l'API REST pour l'application mobile Somewhere.

## Configuration

### Base URL

```
http://votre-serveur:8000/api
```

En développement local:
```
http://192.168.1.104:8000/api
```

### Headers requis

```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer {access_token}  # Pour les routes protégées
```

---

## Authentification

### Format des réponses d'authentification

```json
{
  "message": "Success",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "phone": "+237600000000",
    "firstName": "John",
    "lastName": "Doe",
    "sex": "male",
    "nuiNumber": "123456789",
    "cniNumber": "CNI123456",
    "cniExpirationDate": "2025-12-31T00:00:00.000Z",
    "settings": {
      "language": "fr",
      "unit": "metric",
      "notifications": "enabled",
      "mapType": "GoogleMap",
      "proofOfResidence": null,
      "proofOfResidenceDate": null,
      "googleSearch": true,
      "isCityMapper": false,
      "darkMode": false
    }
  },
  "access_token": "1|abc123...",
  "refresh_token": "xyz789...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

---

### POST /api/auth/register

Inscription d'un nouvel utilisateur.

**Body:**
```json
{
  "phone": "+237600000000",
  "firstName": "John",
  "lastName": "Doe",
  "civility": "male",
  "cni": "CNI123456",
  "nui": "NUI789",
  "cniExpiration": "2025-12-31",
  "email": "john@example.com",
  "password": "password123",
  "quartier": "Bastos",
  "sousQuartier": "Bastos 1",
  "lieuDit": "Carrefour Bastos",
  "latitude": 3.8667,
  "longitude": 11.5167
}
```

**Réponse:** `201 Created` - AuthResponse

---

### POST /api/auth/login

Connexion utilisateur.

**Body:**
```json
{
  "email": "john@example.com",
  "password": "password123",
  "device_name": "iPhone 14 Pro",
  "device_id": "unique-device-id"
}
```

**Réponse:** `200 OK` - AuthResponse

**Erreur:** `401 Unauthorized`
```json
{
  "message": "Invalid credentials"
}
```

---

### POST /api/auth/login/send-otp

Envoyer un code OTP par SMS pour la connexion (alternative au mot de passe).

**Body:**
```json
{
  "phone": "+237600000000"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "OTP sent successfully",
  "data": {
    "expiresAt": "2024-01-20T15:30:00.000Z"
  }
}
```

> **Note:** En mode développement, le code OTP est inclus dans la réponse pour faciliter les tests.

---

### POST /api/auth/login/otp

Connexion avec code OTP (alternative au mot de passe).

**Body:**
```json
{
  "phone": "+237600000000",
  "code": "123-456",
  "device_name": "iPhone 14 Pro",
  "device_id": "unique-device-id"
}
```

**Réponse:** `200 OK` - AuthResponse

**Erreur:** `401 Unauthorized`
```json
{
  "message": "Invalid or expired code"
}
```

---

### POST /api/auth/send-otp

Envoyer un code OTP par SMS (vérification de téléphone).

**Body:**
```json
{
  "phone": "+237600000000"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "OTP sent successfully",
  "data": {
    "expiresAt": "2024-01-20T15:30:00.000Z"
  }
}
```

---

### POST /api/auth/verify-otp

Vérifier un code OTP.

**Body:**
```json
{
  "phone": "+237600000000",
  "code": "123456"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "OTP verified successfully",
  "data": {
    "valid": true
  }
}
```

---

### POST /api/auth/refresh

Rafraîchir le token d'accès.

**Body:**
```json
{
  "token": "refresh_token_here"
}
```

**Réponse:** `200 OK` - AuthResponse

---

### POST /api/auth/logout

Déconnexion (révoque le token actuel).

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

---

### GET /api/auth/profile

Récupérer le profil de l'utilisateur connecté.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": {
    "id": 1,
    "email": "john@example.com",
    "phone": "+237600000000",
    "firstName": "John",
    "lastName": "Doe",
    "sex": "male",
    "nuiNumber": "NUI789",
    "cniNumber": "CNI123456",
    "cniExpirationDate": "2025-12-31T00:00:00.000Z",
    "settings": { ... },
    "collections": [
      { "id": 1, "name": "Maison", "slug": "maison", "type": "custom" }
    ]
  }
}
```

---

### PUT /api/auth/users/{userId}

Modifier le profil utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "firstName": "John",
  "lastName": "Doe Updated",
  "email": "newemail@example.com",
  "phone": "+237600000001",
  "sex": "male",
  "nuiNumber": "NEW_NUI",
  "cniNumber": "NEW_CNI",
  "cniExpirationDate": "2026-12-31",
  "settings": {
    "language": "en",
    "unit": "imperial",
    "notifications": "disabled",
    "mapType": "ApplePlan",
    "googleSearch": false,
    "isCityMapper": true,
    "darkMode": true
  }
}
```

**Réponse:** `200 OK` - User object

---

### DELETE /api/auth/users/{userId}

Supprimer le compte utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `204 No Content`

---

### POST /api/auth/change-password

Changer le mot de passe.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "oldPassword": "current_password",
  "newPassword": "new_secure_password"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Password changed successfully"
}
```

---

### POST /api/auth/reset-password

Demander une réinitialisation de mot de passe.

**Body:**
```json
{
  "email": "john@example.com"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Password reset link sent to your email"
}
```

---

## Authentification sociale (Google / Apple)

Permet aux utilisateurs de se connecter ou créer un compte via Google ou Apple Sign-In.

### POST /api/auth/social/{provider}

Authentification via un provider social (login ou inscription automatique).

**Providers supportés:** `google`, `apple`

**Body:**
```json
{
  "id_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6Ikp...",
  "device_name": "iPhone 14 Pro",
  "device_id": "unique-device-id",
  "user": {
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| id_token | string | Oui | Token d'identité retourné par Google/Apple |
| device_name | string | Non | Nom de l'appareil |
| device_id | string | Non | Identifiant unique de l'appareil |
| user | object | Non | Données utilisateur (Apple uniquement, première connexion) |

> **Note Apple:** Les données `user.firstName` et `user.lastName` ne sont envoyées que lors de la première autorisation Apple. Stockez-les localement car elles ne seront plus disponibles ensuite.

**Réponse:** `200 OK`
```json
{
  "message": "Authentication successful",
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "firstName": "John",
      "lastName": "Doe"
    },
    "access_token": "1|abc123...",
    "refresh_token": "xyz789...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "authMethods": ["social_google", "phone"],
    "needsPinSetup": true,
    "socialAuth": {
      "provider": "google",
      "linkedGoogle": true,
      "linkedApple": false
    }
  }
}
```

**Erreurs:**
- `400` - Provider invalide
- `401` - Token invalide ou expiré
- `500` - Erreur d'authentification

---

### POST /api/auth/social/{provider}/link

Lier un compte social à un utilisateur authentifié.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "id_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6Ikp...",
  "user": {
    "firstName": "John",
    "lastName": "Doe"
  }
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Account linked successfully",
  "data": {
    "user": {
      "id": 1,
      "email": "john@example.com",
      "linkedGoogle": true,
      "linkedApple": false
    }
  }
}
```

**Erreurs:**
- `400` - Compte déjà lié à ce provider
- `409` - Ce compte social est déjà associé à un autre utilisateur

---

### DELETE /api/auth/social/{provider}/unlink

Délier un compte social de l'utilisateur authentifié.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Account unlinked successfully",
  "data": {
    "user": {
      "id": 1,
      "linkedGoogle": false,
      "linkedApple": true
    }
  }
}
```

**Erreurs:**
- `400` - Compte non lié à ce provider
- `400` - Impossible de délier (aucune autre méthode d'authentification disponible)

---

## Lieux-dits

API pour la recherche et l'autocomplétion des lieux-dits camerounais. Utilisée principalement lors de la création d'adresses.

### GET /api/lieux-dits/search

Recherche autocomplete de lieux-dits. **Route publique.**

**Query params:**
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| q | string | Oui | Terme de recherche (min 2 caractères) |
| city | string | Non | Filtrer par ville |
| limit | number | Non | Nombre de résultats (défaut: 20, max: 50) |

**Exemple:** `GET /api/lieux-dits/search?q=bona&city=Douala&limit=15`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": {
    "query": "bona",
    "results": [
      {
        "id": 1,
        "name": "Bonamoussadi",
        "city": "Douala",
        "region": "Littoral",
        "isVerified": true,
        "usageCount": 245
      },
      {
        "id": 2,
        "name": "Bonaberi",
        "city": "Douala",
        "region": "Littoral",
        "isVerified": true,
        "usageCount": 189
      }
    ],
    "count": 2
  }
}
```

---

### GET /api/lieux-dits/popular

Récupérer les lieux-dits les plus utilisés. **Route publique.**

**Query params:**
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| city | string | Non | Filtrer par ville |
| limit | number | Non | Nombre de résultats (défaut: 20) |

**Exemple:** `GET /api/lieux-dits/popular?city=Douala&limit=10`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": {
    "results": [
      {
        "id": 1,
        "name": "Bonamoussadi",
        "city": "Douala",
        "region": "Littoral",
        "isVerified": true,
        "usageCount": 245
      }
    ],
    "count": 10
  }
}
```

---

### GET /api/lieux-dits/cities

Liste des villes disponibles. **Route publique.**

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": {
    "cities": ["Douala", "Yaoundé", "Bafoussam", "Garoua", "Bamenda"],
    "count": 5
  }
}
```

---

### GET /api/lieux-dits

Liste paginée de tous les lieux-dits. **Route publique.**

**Query params:**
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| city | string | Non | Filtrer par ville |
| perPage | number | Non | Éléments par page (défaut: 50) |

**Exemple:** `GET /api/lieux-dits?city=Yaoundé&perPage=25`

**Réponse:** `200 OK` - Liste paginée

---

## Adresses

### Format d'une adresse

```json
{
  "id": 1,
  "swAddress": "SW-ABC12345-XY7Z",
  "displayName": "Bastos - Bastos 1",
  "latLon": [3.8667, 11.5167],
  "coordinates": {
    "latitude": 3.8667,
    "longitude": 11.5167
  },
  "localization": {
    "quarter": "Bastos",
    "sousQuarter": "Bastos 1",
    "lieuDit": "Carrefour Bastos",
    "officialAddress": null
  },
  "way": {
    "code": "WAY001",
    "displayName": "Rue de Bastos"
  },
  "houseType": "villa",
  "homeStatus": "proprietaire",
  "description": "Villa avec jardin",
  "verificationStatus": "pending",
  "createdAt": "2024-01-20T10:00:00.000Z",
  "updatedAt": "2024-01-20T10:00:00.000Z"
}
```

---

### POST /api/addresses

Créer une nouvelle adresse. **Multipart/form-data**

**Headers:** `Authorization: Bearer {access_token}`

**Body (form-data):**
| Champ | Type | Requis | Description |
|-------|------|--------|-------------|
| latitude | number | Oui | Latitude GPS |
| longitude | number | Oui | Longitude GPS |
| accuracy | number | Non | Précision GPS en mètres |
| houseType | string | Oui | `immeuble`, `villa`, `maison`, `studio`, `bureau`, `autre` |
| homeStatus | string | Oui | `locataire`, `residence`, `familiale`, `proprietaire`, `commercial` |
| quarter | string | Oui | Nom du quartier |
| subQuarter | string | Non | Sous-quartier |
| lieuDit | string | Non | Lieu-dit |
| description | string | Non | Description libre |
| honorDeclaration | boolean | Oui | Doit être `true` |
| signature | string | Oui | Données SVG de la signature |
| video | file | Non | Vidéo de preuve (mp4, mov, avi, webm, max 100MB) |

**Réponse:** `201 Created` - Address object

---

### GET /api/addresses

Liste des adresses de l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": [
    { /* Address */ },
    { /* Address */ }
  ]
}
```

---

### GET /api/addresses/{id}

Détail d'une adresse.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Address object

---

### GET /api/addresses/sw/{swAddress}

Rechercher une adresse par son code SW.

**Headers:** `Authorization: Bearer {access_token}`

**Exemple:** `GET /api/addresses/sw/SW-ABC12345-XY7Z`

**Réponse:** `200 OK` - Address object

---

### PUT /api/addresses/{id}

Modifier une adresse.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "displayName": "Ma nouvelle maison",
  "houseType": "maison",
  "homeStatus": "proprietaire",
  "quarter": "Nouveau Quartier",
  "subQuarter": "Sous-quartier",
  "lieuDit": "Lieu-dit",
  "description": "Description mise à jour"
}
```

**Réponse:** `200 OK` - Address object

---

### DELETE /api/addresses/{id}

Supprimer une adresse.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `204 No Content`

---

### POST /api/addresses/{id}/share

Partager une adresse par email.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "recipientEmail": "friend@example.com"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Address shared successfully"
}
```

---

### GET /api/addresses/{id}/qr-code

Générer un QR code pour l'adresse.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": {
    "qrCodeUrl": "data:image/png;base64,..."
  }
}
```

---

### POST /api/addresses/scan

Scanner un QR code ou rechercher une adresse.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "qrData": "{\"type\":\"somewhere_address\",\"swAddress\":\"SW-ABC12345-XY7Z\",\"version\":\"1.0\"}"
}
```

Ou simplement:
```json
{
  "qrData": "SW-ABC12345-XY7Z"
}
```

**Réponse:** `200 OK` - Address object

---

### GET /api/addresses/nearby

Rechercher les adresses à proximité.

**Headers:** `Authorization: Bearer {access_token}`

**Query params:**
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| latitude | number | Oui | Latitude de la position |
| longitude | number | Oui | Longitude de la position |
| radius | number | Non | Rayon en km (défaut: 10, max: 50) |

**Exemple:** `GET /api/addresses/nearby?latitude=3.8667&longitude=11.5167&radius=5`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": [
    { /* Address with distance */ }
  ]
}
```

---

## Collections

### Format d'une collection

```json
{
  "id": 1,
  "name": "Mes favoris",
  "slug": "mes-favoris-abc123",
  "description": "Adresses importantes",
  "logo": null,
  "icon": "star",
  "color": "#FF5733",
  "type": "custom",
  "ownerId": 1,
  "addresses": [
    { /* Address */ }
  ],
  "createdAt": "2024-01-20T10:00:00.000Z",
  "updatedAt": "2024-01-20T10:00:00.000Z"
}
```

---

### GET /api/collections

Liste des collections de l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Collection array

---

### POST /api/collections

Créer une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "name": "Mes favoris",
  "description": "Mes adresses favorites",
  "icon": "star",
  "color": "#FF5733",
  "type": "custom"
}
```

**Réponse:** `201 Created` - Collection object

---

### POST /api/auth/users/{userId}/collections

Route alternative pour créer une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "name": "Livraisons",
  "description": "Adresses de livraison",
  "type": "delivery",
  "slug": "livraisons"
}
```

**Réponse:** `201 Created` - Collection object

---

### GET /api/collections/{id}

Détail d'une collection avec ses adresses.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Collection object

---

### PUT /api/collections/{id}

Modifier une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "name": "Nouveau nom",
  "description": "Nouvelle description",
  "icon": "home",
  "color": "#00FF00"
}
```

**Réponse:** `200 OK` - Collection object

---

### DELETE /api/collections/{id}

Supprimer une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `204 No Content`

---

### POST /api/collections/{collectionId}/addresses

Ajouter une adresse à une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "addressId": 1
}
```

**Réponse:** `200 OK` - Collection object (avec addresses)

---

### DELETE /api/collections/{collectionId}/addresses/{addressId}

Retirer une adresse d'une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Collection object

---

### POST /api/collections/{id}/share

Partager une collection.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "recipientEmail": "friend@example.com",
  "permissions": "view"
}
```

`permissions`: `"view"` (lecture seule) ou `"edit"` (modification)

**Réponse:** `200 OK`
```json
{
  "message": "Collection shared successfully"
}
```

---

### GET /api/collections/shared

Collections partagées avec l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Success",
  "data": [
    {
      "id": 1,
      "name": "Collection partagée",
      "permissions": "view",
      "sharedBy": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com"
      },
      "addresses": [...]
    }
  ]
}
```

---

## Attestation de domicile

### GET /api/proof-of-residence

Générer une attestation de domicile PDF.

**Headers:** `Authorization: Bearer {access_token}`

**Query params:**
| Param | Type | Requis | Description |
|-------|------|--------|-------------|
| addressId | number | Oui | ID de l'adresse (doit être vérifiée) |

**Exemple:** `GET /api/proof-of-residence?addressId=1`

**Réponse:** `200 OK`
```json
{
  "message": "Proof of residence generated successfully",
  "data": {
    "url": "http://api.example.com/api/proof-of-residence/download?path=por_1_1_1705750000.pdf",
    "documentNumber": "SW-POR-1-1-ABC12345",
    "generatedAt": "2024-01-20T10:00:00.000Z"
  }
}
```

**Erreur:** `400 Bad Request` (si l'adresse n'est pas vérifiée)
```json
{
  "message": "Address must be verified to generate proof of residence"
}
```

---

## Gestion des erreurs

### Format des erreurs

```json
{
  "message": "Description de l'erreur",
  "errors": {
    "field_name": ["Message d'erreur"]
  }
}
```

### Codes HTTP

| Code | Description |
|------|-------------|
| 200 | Succès |
| 201 | Création réussie |
| 204 | Suppression réussie (pas de contenu) |
| 400 | Requête invalide |
| 401 | Non authentifié (token invalide/expiré) |
| 403 | Non autorisé (pas les permissions) |
| 404 | Ressource non trouvée |
| 409 | Conflit (doublon) |
| 422 | Erreur de validation |
| 500 | Erreur serveur |

### Gestion du token expiré

Quand vous recevez un `401 Unauthorized`:
1. Appelez `POST /api/auth/refresh` avec le `refresh_token`
2. Stockez les nouveaux tokens
3. Réessayez la requête originale

---

## Exemple d'intégration React Native

```typescript
// api.ts
const API_BASE_URL = 'http://192.168.1.104:8000/api';

let accessToken: string | null = null;
let refreshToken: string | null = null;

const api = {
  setTokens(access: string, refresh: string) {
    accessToken = access;
    refreshToken = refresh;
  },

  async request(endpoint: string, options: RequestInit = {}) {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...options.headers,
    };

    if (accessToken) {
      headers['Authorization'] = `Bearer ${accessToken}`;
    }

    const response = await fetch(`${API_BASE_URL}${endpoint}`, {
      ...options,
      headers,
    });

    // Handle token refresh on 401
    if (response.status === 401 && refreshToken) {
      const refreshed = await this.refreshAccessToken();
      if (refreshed) {
        headers['Authorization'] = `Bearer ${accessToken}`;
        return fetch(`${API_BASE_URL}${endpoint}`, { ...options, headers });
      }
    }

    return response;
  },

  async refreshAccessToken(): Promise<boolean> {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/refresh`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: refreshToken }),
      });

      if (response.ok) {
        const data = await response.json();
        accessToken = data.access_token;
        refreshToken = data.refresh_token;
        return true;
      }
    } catch (e) {}
    return false;
  },

  // Auth
  async login(email: string, password: string) {
    const response = await this.request('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    const data = await response.json();
    if (response.ok) {
      this.setTokens(data.access_token, data.refresh_token);
    }
    return { ok: response.ok, data };
  },

  // Addresses
  async getAddresses() {
    const response = await this.request('/addresses');
    return response.json();
  },

  async createAddress(formData: FormData) {
    const response = await fetch(`${API_BASE_URL}/addresses`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${accessToken}`,
        'Accept': 'application/json',
      },
      body: formData, // FormData for multipart
    });
    return response.json();
  },

  // Collections
  async getCollections() {
    const response = await this.request('/collections');
    return response.json();
  },
};

export default api;
```

---

## Demandes de Livraison

### POST /api/delivery-requests

Créer une nouvelle demande de livraison.

**Headers:** Authorization requis

**Body:**
```json
{
  "title": "Livraison colis",
  "description": "Colis fragile - electronique",
  "value": 15000,
  "currency": "XAF",
  "pickup_address_id": 123
}
```

**Réponse:** `201 Created`
```json
{
  "success": true,
  "message": "Demande de livraison creee",
  "data": {
    "id": 1,
    "initiator_id": 42,
    "recipient_id": null,
    "title": "Livraison colis",
    "description": "Colis fragile - electronique",
    "value": "15000.00",
    "currency": "XAF",
    "status": "pending",
    "initiator_confirmed": false,
    "recipient_confirmed": false,
    "pickup_address_id": 123,
    "delivery_address_id": null,
    "share_token": "abc123xyz789...",
    "share_url": "https://somewhere.app/d/abc123xyz789",
    "created_at": "2025-01-25T10:00:00Z",
    "initiator": {
      "id": 42,
      "first_name": "Jean",
      "last_name": "Dupont"
    }
  }
}
```

---

### GET /api/delivery-requests

Lister les demandes de livraison de l'utilisateur.

**Headers:** Authorization requis

**Query params:**
| Param | Description |
|-------|-------------|
| `status` | Filtrer par statuts (séparés par virgule): `pending,accepted,in_progress` |
| `type` | `sent` (initiées), `received` (reçues), `all` (défaut) |
| `page` | Numéro de page |
| `per_page` | Éléments par page (max 50, défaut 20) |

**Réponse:** `200 OK`
```json
{
  "success": true,
  "message": "Demandes de livraison",
  "data": {
    "data": [
      {
        "id": 1,
        "initiator_id": 42,
        "recipient_id": 55,
        "title": "Livraison colis",
        "status": "accepted",
        "share_url": "https://somewhere.app/d/abc123...",
        "initiator": { ... },
        "recipient": { ... },
        "pickup_address": { ... },
        "delivery_address": { ... }
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 20,
      "total": 5,
      "last_page": 1
    }
  }
}
```

---

### GET /api/delivery-requests/{id}

Détail d'une demande de livraison.

**Headers:** Authorization requis

**Réponse:** `200 OK` - Détails complets de la demande

**Erreur:** `403` si l'utilisateur n'est pas participant

---

### GET /api/delivery-requests/token/{token}

Récupérer une demande par token de partage. **Route publique (pas d'auth requise)**

**Réponse:** `200 OK`
```json
{
  "success": true,
  "message": "Demande de livraison",
  "data": {
    "id": 1,
    "title": "Livraison colis",
    "description": "Colis fragile",
    "value": "15000.00",
    "currency": "XAF",
    "status": "pending",
    "initiator": {
      "id": 42,
      "first_name": "Jean",
      "last_name": "D."
    },
    "created_at": "2025-01-25T10:00:00Z"
  }
}
```

**Erreurs:**
- `404` - Token invalide
- `400` - Demande déjà acceptée

---

### PUT /api/delivery-requests/{id}/accept

Accepter une demande de livraison.

**Headers:** Authorization requis

**Body (option 1 - avec adresse existante):**
```json
{
  "address_id": 789
}
```

**Body (option 2 - avec localisation):**
```json
{
  "location": {
    "latitude": 4.0511,
    "longitude": 9.7679
  }
}
```

**Réponse:** `200 OK`
```json
{
  "success": true,
  "message": "Demande acceptee",
  "data": {
    "id": 1,
    "status": "accepted",
    "recipient_id": 55,
    "delivery_address_id": 789,
    "accepted_at": "2025-01-25T11:00:00Z"
  }
}
```

**Erreurs:**
- `400` - Demande déjà acceptée
- `403` - L'initiateur ne peut pas accepter sa propre demande

---

### PUT /api/delivery-requests/{id}/status

Mettre à jour le statut d'une demande.

**Headers:** Authorization requis

**Body:**
```json
{
  "status": "in_progress"
}
```

**Transitions autorisées:**

| Status actuel | Vers | Qui peut |
|---------------|------|----------|
| `pending` | `cancelled` | Initiateur |
| `accepted` | `in_progress` | Initiateur |
| `accepted` | `cancelled` | Initiateur ou Destinataire |

**Réponse:** `200 OK`

---

### PUT /api/delivery-requests/{id}/confirm

Confirmer la clôture d'une livraison.

**Headers:** Authorization requis

**Réponse:** `200 OK`
```json
{
  "success": true,
  "message": "Confirmation enregistree",
  "data": {
    "id": 1,
    "status": "in_progress",
    "initiator_confirmed": true,
    "recipient_confirmed": false
  }
}
```

**Note:** Quand les deux parties confirment, le statut passe à `completed`.

---

### DELETE /api/delivery-requests/{id}

Supprimer/Annuler une demande.

**Headers:** Authorization requis

**Logique:**
- Si `pending`: suppression physique
- Si `accepted` ou `in_progress`: passage à `cancelled`
- Si `completed`: suppression interdite

**Réponse:** `200 OK`

---

## Notes importantes

1. **Tokens**: Le `access_token` expire après 60 minutes. Utilisez le `refresh_token` (valide 30 jours) pour obtenir un nouveau token.

2. **Uploads vidéo**: Utilisez `multipart/form-data` pour l'endpoint de création d'adresse avec vidéo.

3. **QR Codes**: Le QR code contient un JSON encodé avec le format:
   ```json
   {"type":"somewhere_address","swAddress":"SW-XXX","version":"1.0"}
   ```

4. **Vérification d'adresse**: Seules les adresses avec `verificationStatus: "approved"` peuvent générer une attestation de domicile.

5. **Rate limiting**: Les endpoints d'authentification sont limités à 5 requêtes/minute par IP.

---

## Mot de passe oublié

### POST /api/auth/forgot-password/send-otp

Envoyer un OTP pour réinitialiser le mot de passe (via SMS).

**Body:**
```json
{
  "phone": "+237600000000"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "OTP sent successfully",
  "data": {
    "expiresAt": "2024-01-20T15:40:00.000Z"
  }
}
```

---

### POST /api/auth/forgot-password/send-link

Envoyer un lien de réinitialisation par email.

**Body:**
```json
{
  "email": "john@example.com"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "If this email is registered, you will receive a reset link"
}
```

---

### POST /api/auth/forgot-password/verify-otp

Vérifier l'OTP de réinitialisation.

**Body:**
```json
{
  "phone": "+237600000000",
  "code": "123-456"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Code verified successfully",
  "data": {
    "resetToken": "abc123xyz789...",
    "expiresAt": "2024-01-20T15:55:00.000Z"
  }
}
```

---

### POST /api/auth/forgot-password/reset

Réinitialiser le mot de passe après vérification.

**Body:**
```json
{
  "phone": "+237600000000",
  "resetToken": "abc123xyz789...",
  "password": "new_secure_password",
  "password_confirmation": "new_secure_password"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Password reset successfully. Please login with your new password."
}
```

---

## Gestion du compte

### POST /api/account/request-deletion

Demander la suppression du compte (période de grâce de 30 jours).

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "password": "current_password",
  "reason": "Je ne souhaite plus utiliser le service"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Account deletion requested",
  "data": {
    "deletionRequestedAt": "2024-01-20T10:00:00.000Z",
    "deletionScheduledAt": "2024-02-19T10:00:00.000Z",
    "message": "Your account is scheduled for deletion. You can cancel this request within 30 days."
  }
}
```

---

### POST /api/account/cancel-deletion

Annuler une demande de suppression.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "password": "current_password"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Account deletion cancelled"
}
```

---

### POST /api/account/delete-immediately

Supprimer le compte immédiatement (irréversible).

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "password": "current_password",
  "confirmation": "DELETE_MY_ACCOUNT"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Account deleted successfully"
}
```

---

### GET /api/account/deletion-status

Vérifier le statut de suppression du compte.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Deletion pending",
  "data": {
    "hasPendingDeletion": true,
    "deletionRequestedAt": "2024-01-20T10:00:00.000Z",
    "deletionScheduledAt": "2024-02-19T10:00:00.000Z",
    "daysUntilDeletion": 30,
    "reason": "Je ne souhaite plus utiliser le service"
  }
}
```

---

### GET /api/account/export-data

Exporter toutes les données de l'utilisateur (conformité RGPD).

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Data exported successfully",
  "data": {
    "user": { /* données utilisateur */ },
    "settings": { /* paramètres */ },
    "addresses": [ /* adresses */ ],
    "collections": [ /* collections */ ],
    "payments": [ /* paiements */ ],
    "invoices": [ /* factures */ ],
    "proofOfLocations": [ /* attestations */ ],
    "exportedAt": "2024-01-20T10:00:00.000Z"
  }
}
```

---

## Paiements (Fapshi)

### GET /api/payments/config

Obtenir la configuration des paiements.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Payment configuration",
  "data": {
    "proofOfLocationPrice": 1000,
    "currency": "XAF",
    "paymentMethods": ["mobile_money", "orange_money"],
    "isSandbox": false
  }
}
```

---

### POST /api/payments/proof-of-location

Initier un paiement pour une attestation de domicile (checkout Fapshi).

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "addressId": 1,
  "redirectUrl": "somewhere://payment-callback"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Payment initiated",
  "data": {
    "paymentId": 1,
    "transactionId": "TRX123456",
    "paymentLink": "https://pay.fapshi.com/checkout/abc123",
    "amount": 1000,
    "currency": "XAF",
    "status": "pending",
    "expiresAt": "2024-01-21T10:00:00.000Z"
  }
}
```

**Erreurs:**
- `400` - Adresse non vérifiée
- `400` - Attestation active existante pour cette adresse

---

### POST /api/payments/proof-of-location/direct

Paiement direct par Mobile Money.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "addressId": 1,
  "phone": "+237600000000"
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Direct payment initiated",
  "data": {
    "paymentId": 1,
    "transactionId": "TRX123456",
    "amount": 1000,
    "currency": "XAF",
    "status": "pending",
    "message": "Please confirm the payment on your phone"
  }
}
```

---

### GET /api/payments/{id}

Vérifier le statut d'un paiement.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Payment status retrieved",
  "data": {
    "paymentId": 1,
    "transactionId": "TRX123456",
    "externalId": "SW-ABC123",
    "type": "proof_of_location",
    "amount": 1000,
    "currency": "XAF",
    "status": "successful",
    "paymentLink": null,
    "paidAt": "2024-01-20T10:05:00.000Z",
    "failureReason": null,
    "createdAt": "2024-01-20T10:00:00.000Z",
    "proofOfLocation": {
      "id": 1,
      "documentNumber": "SW-POL-1-1-ABC12345",
      "status": "active",
      "issuedAt": "2024-01-20T10:05:00.000Z",
      "expiresAt": "2024-04-20T10:05:00.000Z"
    }
  }
}
```

---

### GET /api/payments

Lister les paiements de l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| perPage | number | Éléments par page (défaut: 15) |

**Réponse:** `200 OK` - Liste paginée des paiements

---

## KYC (Vérification d'identité)

### GET /api/kyc/status

Obtenir le statut KYC de l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "KYC status retrieved",
  "data": {
    "id": 1,
    "status": "pending",
    "level": "basic",
    "completionPercentage": 50,
    "isComplete": false,
    "documents": {
      "cniFront": { "uploaded": true, "verified": false },
      "cniBack": { "uploaded": true, "verified": false },
      "selfie": { "uploaded": false, "verified": false },
      "video": { "uploaded": false }
    },
    "verifications": {
      "phone": true,
      "address": false
    },
    "rejectionReason": null,
    "reviewedAt": null,
    "approvedAt": null,
    "expiresAt": null,
    "createdAt": "2024-01-20T10:00:00.000Z",
    "updatedAt": "2024-01-20T10:00:00.000Z"
  }
}
```

---

### POST /api/kyc/upload/cni-front

Télécharger le recto de la CNI.

**Headers:** `Authorization: Bearer {access_token}`

**Body (multipart/form-data):**
| Champ | Type | Description |
|-------|------|-------------|
| image | file | Image JPEG/PNG (max 5MB) |

**Réponse:** `200 OK` - KYC status mis à jour

---

### POST /api/kyc/upload/cni-back

Télécharger le verso de la CNI.

**Headers:** `Authorization: Bearer {access_token}`

**Body (multipart/form-data):**
| Champ | Type | Description |
|-------|------|-------------|
| image | file | Image JPEG/PNG (max 5MB) |

**Réponse:** `200 OK` - KYC status mis à jour

---

### POST /api/kyc/upload/selfie

Télécharger un selfie de vérification.

**Headers:** `Authorization: Bearer {access_token}`

**Body (multipart/form-data):**
| Champ | Type | Description |
|-------|------|-------------|
| image | file | Image JPEG/PNG (max 5MB) |

**Réponse:** `200 OK` - KYC status mis à jour

---

### POST /api/kyc/upload/video

Télécharger une vidéo de vérification.

**Headers:** `Authorization: Bearer {access_token}`

**Body (multipart/form-data):**
| Champ | Type | Description |
|-------|------|-------------|
| video | file | Vidéo MP4/MOV (max 50MB) |

**Réponse:** `200 OK` - KYC status mis à jour

---

### POST /api/kyc/submit

Soumettre le KYC pour vérification.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "KYC submitted for review",
  "data": {
    "status": "in_review"
  }
}
```

**Erreur:** `400` si documents incomplets
```json
{
  "message": "Please complete all required documents before submitting",
  "errors": {
    "completionPercentage": 75,
    "missing": ["selfie"]
  }
}
```

---

## Attestations de domicile (Proof of Location)

### GET /api/proof-of-location

Lister les attestations de l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| perPage | number | Éléments par page (défaut: 15) |

**Réponse:** `200 OK` - Liste paginée

---

### GET /api/proof-of-location/active

Obtenir uniquement les attestations actives.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "Active proofs retrieved",
  "data": [
    {
      "id": 1,
      "documentNumber": "SW-POL-1-1-ABC12345",
      "status": "active",
      "isActive": true,
      "isExpired": false,
      "address": {
        "id": 1,
        "swAddress": "SW-ABC123-XY7Z",
        "displayName": "Maison Bastos"
      },
      "issuedAt": "2024-01-20T10:00:00.000Z",
      "expiresAt": "2024-04-20T10:00:00.000Z",
      "daysUntilExpiry": 90,
      "downloadUrl": "http://api.example.com/api/proof-of-location/1/download",
      "webUrl": "http://api.example.com/web/proof/abc123token...",
      "qrCodeData": {
        "url": "http://api.example.com/web/proof/abc123token...",
        "document_number": "SW-POL-1-1-ABC12345",
        "valid_until": "2024-04-20T10:00:00.000Z"
      },
      "downloadCount": 3,
      "qrScanCount": 15,
      "createdAt": "2024-01-20T10:00:00.000Z"
    }
  ]
}
```

---

### GET /api/proof-of-location/{id}

Détail d'une attestation.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Détail complet de l'attestation

---

### GET /api/proof-of-location/{id}/download

Télécharger le PDF de l'attestation.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Fichier PDF

---

### GET /api/proof-of-location/{id}/qr-code

Générer un QR code pour accès web.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "QR code generated",
  "data": {
    "qrCode": "data:image/png;base64,...",
    "qrData": {
      "token": "abc123xyz...",
      "url": "http://api.example.com/web/access/abc123xyz...",
      "type": "proof_of_location",
      "expiresAt": "2024-01-20T11:00:00.000Z",
      "validityMinutes": 60
    },
    "proof": { /* données de l'attestation */ }
  }
}
```

---

### GET /api/proof-of-location/verify/{token} (Public)

Vérifier une attestation via QR code token.

**Réponse:** `200 OK`
```json
{
  "message": "Proof of location verified",
  "data": {
    "documentNumber": "SW-POL-1-1-ABC12345",
    "status": "active",
    "isActive": true,
    "isExpired": false,
    "holder": {
      "firstName": "John",
      "lastName": "Doe"
    },
    "address": {
      "swAddress": "SW-ABC123-XY7Z",
      "displayName": "Maison Bastos",
      "quarter": "Bastos",
      "subQuarter": "Bastos 1"
    },
    "issuedAt": "2024-01-20T10:00:00.000Z",
    "expiresAt": "2024-04-20T10:00:00.000Z",
    "verificationUrl": "http://api.example.com/web/proof/verify/abc123..."
  }
}
```

---

## Factures

### GET /api/invoices

Lister les factures de l'utilisateur.

**Headers:** `Authorization: Bearer {access_token}`

**Query params:**
| Param | Type | Description |
|-------|------|-------------|
| perPage | number | Éléments par page (défaut: 15) |

**Réponse:** `200 OK`
```json
{
  "message": "Invoices retrieved",
  "data": [
    {
      "id": 1,
      "invoiceNumber": "SW-INV-202401-0001",
      "description": "Proof of Location - Document officiel (SW-ABC123-XY7Z)",
      "amount": 1000,
      "currency": "XAF",
      "taxAmount": 0,
      "totalAmount": 1000,
      "invoiceDate": "2024-01-20",
      "dueDate": null,
      "paidAt": "2024-01-20T10:05:00.000Z",
      "isPaid": true,
      "webUrl": "http://api.example.com/web/invoice/token123...",
      "downloadUrl": "http://api.example.com/api/invoices/1/download",
      "createdAt": "2024-01-20T10:05:00.000Z"
    }
  ],
  "meta": { /* pagination */ }
}
```

---

### GET /api/invoices/{id}

Détail d'une facture.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Détail de la facture

---

### GET /api/invoices/{id}/download

Télécharger le PDF de la facture.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK` - Fichier PDF

---

### GET /api/invoices/{id}/qr-code

Générer un QR code pour accès web à la facture.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "QR code generated",
  "data": {
    "qrCode": "data:image/png;base64,...",
    "qrData": {
      "token": "abc123xyz...",
      "url": "http://api.example.com/web/access/abc123xyz...",
      "type": "invoice",
      "expiresAt": "2024-01-20T11:00:00.000Z"
    },
    "invoice": { /* données de la facture */ }
  }
}
```

---

## Accès Web via QR Code

L'application mobile peut générer des QR codes temporaires permettant d'accéder aux documents sur un ordinateur.

### POST /api/web-access/dashboard-qr

Générer un QR code pour accéder au dashboard web.

**Headers:** `Authorization: Bearer {access_token}`

**Body:**
```json
{
  "validityMinutes": 60
}
```

**Réponse:** `200 OK`
```json
{
  "message": "Dashboard QR code generated",
  "data": {
    "qrCode": "data:image/png;base64,...",
    "qrData": {
      "token": "abc123xyz...",
      "url": "http://api.example.com/web/access/abc123xyz...",
      "type": "dashboard",
      "expiresAt": "2024-01-20T11:00:00.000Z",
      "validityMinutes": 60
    }
  }
}
```

---

### POST /api/web-access/kyc-qr

Générer un QR code pour voir le statut KYC.

**Headers:** `Authorization: Bearer {access_token}`

**Réponse:** `200 OK`
```json
{
  "message": "KYC status QR code generated",
  "data": {
    "qrCode": "data:image/png;base64,...",
    "qrData": {
      "token": "abc123xyz...",
      "url": "http://api.example.com/web/access/abc123xyz...",
      "type": "kyc_status",
      "expiresAt": "2024-01-20T10:30:00.000Z"
    }
  }
}
```

---

### GET /api/web-access/validate/{token} (Public)

Valider un token d'accès web (utilisé par le portail web).

**Réponse:** `200 OK`
```json
{
  "message": "Token validated",
  "data": {
    "type": "dashboard",
    "expiresAt": "2024-01-20T11:00:00.000Z",
    "user": {
      "id": 1,
      "firstName": "John",
      "lastName": "Doe"
    },
    "dashboard": {
      "settings": { /* paramètres */ },
      "kycStatus": { "status": "approved", "isApproved": true },
      "activeProofsCount": 2,
      "recentProofs": [ /* attestations récentes */ ],
      "recentInvoices": [ /* factures récentes */ ]
    }
  }
}
```

---

## Portail Web

Le portail web permet aux utilisateurs d'accéder à leurs documents depuis un ordinateur en scannant un QR code depuis l'application mobile.

### URLs du portail

| Route | Description |
|-------|-------------|
| `/web/access/{token}` | Point d'entrée principal (redirige selon le type) |
| `/web/proof/{token}` | Visualiser une attestation |
| `/web/proof/{token}/download` | Télécharger le PDF |
| `/web/invoice/{token}` | Visualiser une facture |
| `/web/invoice/{token}/download` | Télécharger le PDF |

---

## Webhook Fapshi

### POST /api/webhooks/fapshi

Endpoint pour recevoir les notifications de paiement Fapshi.

**Note:** Cette route est appelée automatiquement par Fapshi quand le statut d'un paiement change. Elle ne nécessite pas d'authentification utilisateur.

**Payload (envoyé par Fapshi):**
```json
{
  "transId": "TRX123456",
  "status": "SUCCESSFUL",
  "amount": 1000,
  "phone": "+237600000000",
  "medium": "mobile money"
}
```

**Statuts possibles:** `SUCCESSFUL`, `FAILED`, `EXPIRED`

---

## Configuration requise

### Variables d'environnement

```env
# Fapshi Payment Gateway
FAPSHI_API_USER=votre_api_user
FAPSHI_API_KEY=FAK_xxx  # ou FAK_TEST_xxx pour sandbox
FAPSHI_WEBHOOK_SECRET=secret_optionnel
FAPSHI_PROOF_OF_LOCATION_PRICE=1000

# Proof of Location
PROOF_OF_LOCATION_VALIDITY_MONTHS=3

# Account Settings
ACCOUNT_DELETION_GRACE_PERIOD_DAYS=30

# KYC Settings
KYC_VALIDITY_MONTHS=12

# Company Information (pour les factures)
COMPANY_NAME=Somewhere
COMPANY_ADDRESS="Douala, Cameroun"
COMPANY_PHONE="+237 600 000 000"
COMPANY_EMAIL=contact@somewhere.cm
```

---

## Commandes Artisan

| Commande | Description |
|----------|-------------|
| `php artisan proof:expire` | Marquer les attestations expirées |
| `php artisan accounts:process-deletions` | Traiter les suppressions planifiées |
| `php artisan tokens:cleanup` | Nettoyer les tokens web expirés |

Ces commandes sont programmées pour s'exécuter automatiquement via le scheduler Laravel.
