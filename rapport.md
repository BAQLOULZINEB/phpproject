# Rapport Technique Détaillé
## Développement d'un Système de Recommandation Intelligent pour E-commerce
### Architecture de Données Distribuées avec PySpark et MinIO

---

## Table des Matières
1. [Introduction et Contexte](#introduction-et-contexte)
2. [Objectifs Pédagogiques et Métier](#objectifs-pédagogiques-et-métier)
3. [État de l'Art et Justification Technologique](#état-de-lart-et-justification-technologique)
4. [Dataset et Préparation des Données](#dataset-et-préparation-des-données)
5. [Architecture du Système](#architecture-du-système)
6. [Stack Technique Détaillée](#stack-technique-détaillée)
7. [Pipeline Machine Learning](#pipeline-machine-learning)
8. [Implémentation et Développement](#implémentation-et-développement)
9. [Évaluation et Résultats](#évaluation-et-résultats)
10. [Déploiement et Intégration](#déploiement-et-intégration)
11. [Conclusion et Perspectives](#conclusion-et-perspectives)

---

## 1. Introduction et Contexte

### 1.1 Problématique
Les plateformes e-commerce contemporaines génèrent des volumes massifs de données utilisateurs (millions d'interactions quotidiennes). Le défi principal est de:
- **Traiter** efficacement ces données hétérogènes et distribuées
- **Analyser** les comportements utilisateurs à l'échelle
- **Générer** des recommandations pertinentes et personnalisées en temps réel
- **Scalabiliser** le système pour supporter la croissance exponentielle des données

### 1.2 Solution Proposée
Un système de recommandation intelligent basé sur:
- **Technologie Big Data**: PySpark pour traitement distribué
- **Algorithme de filtrage collaboratif**: ALS (Alternating Least Squares) de MLlib
- **Stockage objet**: MinIO (compatible S3) pour gestion efficace des données
- **API REST**: FastAPI pour exposition des recommandations
- **Pipeline automatisé**: ETL distribuée pour nettoyage et transformation

### 1.3 Contexte Académique
- **Durée**: 8 semaines
- **Équipe**: 2 étudiants
- **Formation**: 4IASD (Master Big Data & Intelligence Artificielle)
- **Partenaires Technologiques**: Apache Spark, MinIO, Kaggle

---

## 2. Objectifs Pédagogiques et Métier

### 2.1 Objectifs Pédagogiques
#### Machine Learning
- Implémenter un moteur de recommandation basé sur le filtrage collaboratif
- Maîtriser les algorithmes ALS et évaluation des modèles (RMSE, Precision@K)
- Comprendre la matrice utilisateur-produit et ses applications

#### Big Data & Scalabilité
- Manipuler des volumes massifs de données (millions d'interactions)
- Utiliser PySpark pour traitement distribué efficace
- Optimiser les performances avec partitionnement et caching

#### Stockage & Infrastructure
- Configurer et gérer MinIO comme Object Storage S3
- Implémenter une pipeline de données distribuée
- Comprendre les concepts de fault-tolerance et replication

#### API & Intégration
- Développer une API REST avec FastAPI
- Intégrer le modèle ML dans une application production-ready
- Gérer les dépendances et versioning du code

#### Collaboratif & DevOps
- Utiliser Git pour gestion collaborative du code
- Documenter le projet de manière professionnelle
- Présenter les résultats de manière claire et convaincante

### 2.2 Objectifs Métier
- **Augmenter** le taux de conversion par recommandations pertinentes
- **Réduire** le temps de traitement et améliorer la latence des requêtes
- **Analyser** les tendances produits et comportements utilisateurs
- **Optimiser** les ressources informatiques par une architecture distribuée
- **Adapter** le système à la croissance exponentielle des données

---

## 3. État de l'Art et Justification Technologique

### 3.1 Systèmes de Recommandation
#### Approches Existantes
1. **Filtrage Basé Contenu**: Recommande sur base d'attributs produits
2. **Filtrage Collaboratif**: Exploite les comportements similaires des utilisateurs
3. **Filtrage Hybride**: Combine les deux approches précédentes

#### Choix: Filtrage Collaboratif ALS
**Justification**:
- Pas besoin de métadonnées complètes des produits
- Détecte les patterns cachés dans les interactions
- Scalabilité éprouvée sur grands volumes
- Performance temps réel acceptable

### 3.2 Technologie Big Data: Apache Spark
**Avantages**:
- Traitement distribué en mémoire (100x plus rapide que Hadoop)
- Support natif du Machine Learning via MLlib
- Compatible avec minIO et HDFS
- Écosystème mature et large communauté

### 3.3 Stockage: MinIO
**Avantages**:
- Compatible API Amazon S3
- High-performance Object Storage
- Self-hosted (pas de dépendance cloud)
- Support du versioning et du replication

### 3.4 Stack API: FastAPI
**Avantages**:
- Performance haute (comparable à Go)
- Validation automatique avec Pydantic
- Documentation automatique (Swagger)
- Support async/await natif

---

## 4. Dataset et Préparation des Données

### 4.1 Source: Retailrocket Recommender System Dataset

#### Fichiers Principaux
```
events.csv
├── Colonnes: timestamp, visitorid, event, itemid, transactionid
├── Volume: ~900K transactions
├── Interactions: Views, Carts, Purchases (poids différent)

item_properties.csv
├── Colonnes: itemid, property, value
├── Propriétés: category, brand, price, subcategory
├── Permettent enrichissement des recommandations

category_tree.csv
├── Hiérarchie des catégories
├── Facilite filtrage et segmentation
```

### 4.2 Préparation et Nettoyage

#### Étape 1: Chargement dans MinIO
```python
# Stockage brut en S3/MinIO
- events.csv → /raw/events/
- item_properties.csv → /raw/properties/
- Format: Parquet pour optimisation
```

#### Étape 2: Nettoyage Distribué avec PySpark
**Opérations**:
- Suppression des doublons et valeurs manquantes
- Normalisation des timestamps
- Filtrage des utilisateurs/produits inactifs
- Détection et traitement des anomalies

**Résultats Attendus**:
- Réduction de 20-30% du volume (suppression bruits)
- Dataset uniforme et structuré
- Prêt pour feature engineering

#### Étape 3: Construction de la Matrice
```
Matrice Utilisateur × Produit
└── Valeurs: Poids des interactions
    ├── View: 1
    ├── Cart: 2
    └── Purchase: 3
```

#### Étape 4: Split Train/Test
- **Train**: 80% (données historiques)
- **Test**: 20% (validation temporelle)
- **Stratégie**: Chronologique pour validité réelle

### 4.3 Statistiques du Dataset

| Métrique | Valeur |
|----------|--------|
| Nombre d'utilisateurs | ~1.4M |
| Nombre de produits | ~430K |
| Nombre d'interactions | ~2.7M |
| Sparsité de la matrice | ~99.95% |
| Densité moyenne par utilisateur | 2-3 produits |

---

## 5. Architecture du Système

### 5.1 Pipeline Global

```
┌─────────────────────────────────────────────────────────────┐
│                      Pipeline Architecture                   │
└─────────────────────────────────────────────────────────────┘

[Données Brutes - Kaggle/S3]
            ↓
[MinIO Object Storage - Stockage Distribuée]
            ↓
[PySpark - Nettoyage & Transformation]
            ├── Suppression doublons
            ├── Normalisation
            └── Construction matrice
            ↓
[Feature Engineering]
            ├── TF-IDF encodage
            ├── Normalization
            └── Scaling
            ↓
[Split Train/Test - 80/20]
            ↓
[MLlib ALS - Entraînement Modèle]
            ├── Rank: 10-50
            ├── Iterations: 10-20
            └── RegParam: 0.01-0.1
            ↓
[Évaluation Metrics]
            ├── RMSE (Root Mean Square Error)
            ├── Precision@K
            ├── Recall@K
            └── NDCG (Normalized Discounted Cumulative Gain)
            ↓
[Model Serialization - Parquet]
            ↓
[FastAPI Application]
            ├── Endpoint: GET /recommend/{user_id}
            ├── Endpoint: GET /similar/{product_id}
            └── Endpoint: GET /trending
            ↓
[Frontend Integration - PHP/JavaScript]
            ├── Product Cards
            ├── Recommendation Widget
            └── Quick View Modal
            ↓
[Monitoring & Logging]
            └── Metrics collection, errors tracking
```

### 5.2 Composants Principaux

#### 1. Data Ingestion Layer
- MinIO as S3 bucket
- Parquet format for efficiency
- Partitioning by date/category

#### 2. Processing Layer
- PySpark Core for ETL
- Distributed computing across nodes
- Fault-tolerant execution

#### 3. ML Layer
- Spark MLlib for ALS
- Model training and evaluation
- Hyperparameter optimization

#### 4. Storage Layer
- Trained model serialization
- Predictions cache
- Version control

#### 5. API Layer
- FastAPI REST endpoints
- Request validation
- Response caching

#### 6. Integration Layer
- PHP backend integration
- JavaScript frontend binding
- Real-time recommendation loading

---

## 6. Stack Technique Détaillée

### 6.1 Langage & Runtimes

| Composant | Technologie | Version | Rôle |
|-----------|-------------|---------|------|
| Langage Principal | Python | 3.8+ | Développement |
| Big Data | Apache Spark | 3.0+ | Processing distribué |
| ML Library | Spark MLlib | Intégré | Collaborative filtering |
| Data Processing | NumPy, Pandas | Latest | Manipulation données |

### 6.2 Stockage & Infrastructure

| Composant | Technologie | Configuration |
|-----------|-------------|----------------|
| Object Storage | MinIO | Buckets: raw, processed, models |
| SGBDR | PostgreSQL | Métadonnées, user sessions |
| Cache | Redis | Session cache, hot data |
| Message Queue | RabbitMQ (optionnel) | Async job processing |

### 6.3 API & Web

| Composant | Technologie | Usage |
|-----------|-------------|-------|
| Backend API | FastAPI | REST API with async |
| Frontend | PHP/JavaScript | Server-side rendering + AJAX |
| Web Server | Nginx/Apache | Reverse proxy, load balancing |
| Documentation | Swagger UI | Auto-generated API docs |

### 6.4 DevOps & Versioning

| Composant | Technologie | Usage |
|-----------|-------------|-------|
| Version Control | Git | GitHub/GitLab |
| CI/CD | GitHub Actions | Automated testing |
| Containerization | Docker | Reproducible environments |
| Orchestration | Docker Compose | Local deployment |

### 6.5 Dépendances Python Clés

```python
# Core Big Data
pyspark==3.0.0+
numpy>=1.19.0
pandas>=1.1.0

# Machine Learning
scikit-learn>=0.23.0

# API
fastapi>=0.68.0
uvicorn>=0.15.0
pydantic>=1.8.0

# Data Storage
boto3>=1.17.0  # AWS S3 SDK (MinIO compatible)
pyarrow>=4.0.0  # Parquet serialization

# Database
psycopg2-binary>=2.9.0  # PostgreSQL driver
redis>=3.5.0

# Utilities
python-dotenv>=0.19.0
requests>=2.26.0
```

---

## 7. Pipeline Machine Learning

### 7.1 Algorithme ALS (Alternating Least Squares)

#### Mathématique
```
Objectif: Minimiser ||R - U*V^T||²_F + λ(||U||²_F + ||V||²_F)

Où:
- R: Matrice interaction (utilisateur × produit)
- U: Matrice features utilisateurs (m × k)
- V: Matrice features produits (n × k)
- λ: Paramètre de régularisation
- ||.||_F: Norme Frobenius
```

#### Avantages
- Converge rapidement pour données sparses
- Parallélisable efficacement
- Pas besoin d'information contextuelle

#### Hyperparamètres
```python
rank = 50              # Dimension de factorisation
maxIter = 20           # Nombre d'itérations
regParam = 0.01        # Regularization parameter
alpha = 1.0            # Implicit feedback weight
```

### 7.2 Entraînement du Modèle

#### Étape 1: Préparation des données
```python
# Load from MinIO
df = spark.read.parquet("s3a://bucket/processed/")

# Convert to RDD format
ratings = df.rdd.map(lambda x: (x.user_id, x.item_id, x.rating))

# Create Spark RDD
training_data, test_data = ratings.randomSplit([0.8, 0.2])
```

#### Étape 2: Entraînement
```python
from pyspark.ml.recommendation import ALS

als = ALS(
    rank=50,
    maxIter=20,
    regParam=0.01,
    userCol="user_id",
    itemCol="item_id",
    ratingCol="rating",
    coldStartStrategy="drop"
)

model = als.fit(training_data)
```

#### Étape 3: Validation et Tuning
```python
from pyspark.ml.evaluation import RegressionEvaluator

evaluator = RegressionEvaluator(
    metricName="rmse",
    labelCol="rating",
    predictionCol="prediction"
)

predictions = model.transform(test_data)
rmse = evaluator.evaluate(predictions)

# Grid search pour hyperparamètres
param_grid = [
    {rank: [10, 20, 50], regParam: [0.01, 0.1, 1.0]},
]
```

### 7.3 Métriques d'Évaluation

#### RMSE (Root Mean Square Error)
```
RMSE = √(1/n * Σ(y_true - y_pred)²)
```
**Interprétation**: Erreur moyenne de prédiction (unité: rating)
**Cible**: < 0.8

#### Precision@K
```
Precision@K = |Recommended ∩ Relevant| / K
```
**Interprétation**: Proportion de recommandations pertinentes
**Cible**: > 0.6

#### Recall@K
```
Recall@K = |Recommended ∩ Relevant| / |Relevant|
```
**Interprétation**: Couverture des items pertinents
**Cible**: > 0.5

#### NDCG (Normalized DCG)
```
NDCG = DCG / IDCG
```
**Interprétation**: Qualité du classement (0-1)
**Cible**: > 0.7

---

## 8. Implémentation et Développement

### 8.1 Phase 1: Exploration & Préparation (Semaines 1-2)

#### Semaine 1: Collecte et Stockage
**Livrables**:
- [ ] Dataset téléchargé depuis Kaggle
- [ ] MinIO configurée et accessible
- [ ] Données uploadées en S3/MinIO
- [ ] Parquet format optimisé
- [ ] Documentation du storage

**Activités**:
- Configuration MinIO locale/cloud
- Création des buckets nécessaires
- Scripts d'upload automatisé
- Tests d'accès S3

#### Semaine 2: Prétraitement
**Livrables**:
- [ ] Pipeline PySpark fonctionnelle
- [ ] Dataset nettoyé et normalisé
- [ ] Matrice utilisateur-produit construite
- [ ] Train/Test split effectué
- [ ] Statistiques exploratoires

**Activités**:
- EDA (Exploratory Data Analysis)
- Nettoyage et validation
- Feature engineering
- Split et validation croisée

### 8.2 Phase 2: Modélisation & Évaluation (Semaines 3-5)

#### Semaine 3: Entraînement ALS
**Livrables**:
- [ ] Modèle ALS entraîné
- [ ] Premiers résultats (RMSE, etc.)
- [ ] Logs d'entraînement
- [ ] Model checkpoints sauvegardés

**Activités**:
- Implémentation pipeline ALS
- Entraînement sur données train
- Sauvegarde du modèle
- Monitoring de la convergence

#### Semaine 4: Évaluation Détaillée
**Livrables**:
- [ ] Rapport d'évaluation complète
- [ ] Metrics calculées (RMSE, Precision@K, Recall@K)
- [ ] Analyse des erreurs
- [ ] Visualisations résultats
- [ ] Comparaison baselines

**Activités**:
- Calcul metrics multiples
- Analyse des cas d'erreur
- Génération des rapports
- Documentation des résultats

#### Semaine 5: Optimisation
**Livrables**:
- [ ] Modèle optimisé avec meilleurs hyperparamètres
- [ ] Comparaison avant/après
- [ ] Recommandations pour production
- [ ] Version finale du modèle

**Activités**:
- Grid search hyperparamètres
- Optimisation de la performance
- Tests de latence
- Ajustement selon les métriques

### 8.3 Phase 3: Déploiement & Finalisation (Semaines 6-8)

#### Semaine 6: API Développement
**Livrables**:
- [ ] API FastAPI fonctionnelle
- [ ] Endpoints de recommandation
- [ ] Tests unitaires passants
- [ ] Documentation API (Swagger)
- [ ] Error handling

**Activités**:
- Développement endpoints
- Chargement modèle en mémoire
- Caching des résultats
- Tests automatisés
- Documentation code

#### Semaine 7: Visualisation & Dashboard
**Livrables**:
- [ ] Dashboard interactif
- [ ] Visualisations clés
- [ ] Statistiques en temps réel
- [ ] Graphiques d'analyse
- [ ] Interface utilisateur

**Activités**:
- Design du dashboard
- Intégration avec API
- Visualisations dynamiques
- Tests d'UX

#### Semaine 8: Rapport & Soutenance
**Livrables**:
- [ ] Rapport technique complet
- [ ] Code source commenté
- [ ] Documentation complète
- [ ] Slides de présentation
- [ ] Vidéo démonstration

**Activités**:
- Rédaction rapport
- Finalisation code
- Préparation présentation
- Tests de soutenance

---

## 9. Évaluation et Résultats

### 9.1 Critères d'Évaluation

#### Critères Techniques
- **Correctness**: Modèle entraîné sans erreurs
- **Performance**: RMSE < 0.8, Precision@K > 0.6
- **Scalabilité**: Traitement de millions d'interactions
- **Latence**: Temps réponse API < 200ms

#### Critères de Code
- **Qualité**: Code lisible, commenté, suivant PEP8
- **Testing**: Coverage > 80% pour fonctions critiques
- **Documentation**: README complète, docstrings présentes
- **Versioning**: Git history claire et logique

#### Critères Métier
- **Impact**: Recommandations pertinentes
- **Intégration**: Fonctionnement dans l'e-commerce
- **Monitoring**: Logs et alertes configurées

### 9.2 Résultats Attendus

#### Métriques de Performance
```
Model Performance:
├── RMSE: 0.75 ± 0.05
├── Precision@10: 0.65 ± 0.05
├── Recall@10: 0.55 ± 0.05
├── NDCG@10: 0.72 ± 0.04
└── Coverage: 85-90%

Infrastructure Performance:
├── Training Time: < 30 min (pour dataset complet)
├── Prediction Latency: 50-150ms
├── Memory Usage: 2-4GB pour model + data
└── Storage: 500MB-1GB (compressed)
```

#### Résultats d'Affaires
- Augmentation CTR (Click-Through Rate) estimée: +15-20%
- Augmentation du panier moyen: +10-15%
- Réduction du temps de découverte produits: -30%
- Satisfaction utilisateur (NPS): +20 points

---

## 10. Déploiement et Intégration

### 10.1 Architecture de Production

```
┌─────────────────────────────────────────┐
│        Production Deployment             │
├─────────────────────────────────────────┤

Users (E-commerce Platform)
        ↓
    [Nginx - Load Balancer]
        ↓
    [FastAPI Container 1]
    [FastAPI Container 2]  ← API Cluster
    [FastAPI Container 3]
        ↓
    [Model Cache - Redis]
        ↓
    [Spark Cluster - Batch Processing]
        ↓
    [MinIO - Model Storage]
        ↓
    [PostgreSQL - Metadata]
```

### 10.2 Intégration avec E-commerce

#### Frontend Integration
```javascript
// JavaScript for real-time recommendations
fetch('/api/recommendations?user_id=' + userId)
  .then(response => response.json())
  .then(data => {
    renderRecommendationWidget(data);
  });
```

#### Backend Integration
```php
// PHP backend for server-side rendering
$recommendations = curl_request('http://api:8000/recommend/' . $user_id);
include 'recommender_widget.php';
```

### 10.3 Monitoring & Logging

#### Métriques Supervisées
- API response time
- Model performance drift
- Cache hit rate
- Error rates
- User engagement

#### Logs Collectés
```
- Model training logs
- API request logs
- Performance metrics
- Error traces
- User interactions
```

---

## 11. Conclusion et Perspectives

### 11.1 Réalisations

✓ Système de recommandation scalable développé
✓ Pipeline Big Data automatisée fonctionnelle
✓ Modèle ML entraîné avec performances acceptables
✓ API REST intégrée au e-commerce
✓ Documentation et code production-ready

### 11.2 Points Clés du Projet

1. **Scalabilité**: Architecture capable de traiter millions d'interactions
2. **Performance**: RMSE optimisé et latence acceptable
3. **Fiabilité**: Pipeline avec gestion d'erreurs et fallbacks
4. **Maintenabilité**: Code documenté et tests couverts
5. **Business Value**: Impact mesurable sur conversion

### 11.3 Leçons Apprises

- Importance du nettoyage données (80% du temps)
- Trade-off entre qualité et performance
- Nécessité du monitoring en production
- Collaboration efficace avec Git workflow

### 11.4 Améliorations Futures

#### Court Terme (3-6 mois)
- [ ] Filtrage hybride (contenu + collaboratif)
- [ ] Modèle contextuel (saison, heure, géolocalisation)
- [ ] A/B testing des recommandations
- [ ] Dashboard avancé avec drill-down

#### Moyen Terme (6-12 mois)
- [ ] Deep Learning models (Neural Collaborative Filtering)
- [ ] Real-time streaming avec Kafka
- [ ] Multi-armed bandit pour exploitation/exploration
- [ ] Explainability des recommandations

#### Long Terme (> 1 an)
- [ ] Federated Learning pour privacy
- [ ] Cross-domain recommendations
- [ ] Knowledge graph integration
- [ ] Quantum computing exploration

### 11.5 Recommandations Finales

Pour le succès du projet en production:

1. **Maintenance continue**: Monitoring des dérives de modèle
2. **Feedback loop**: Collecte des interactions pour re-training
3. **A/B testing**: Validation des améliorations réelles
4. **Documentation**: Mise à jour constante du système
5. **Équipe**: Déploiement d'expertise Big Data/ML

---

## Références & Ressources

### Papiers Scientifiques
- Koren et al. (2009): Matrix Factorization Techniques for Recommender Systems
- Collaborative Filtering: A Machine Learning Perspective - Ekstrand et al.

### Documentation Officielle
- Apache Spark: https://spark.apache.org/
- MinIO: https://min.io/
- FastAPI: https://fastapi.tiangolo.com/
- Kaggle Dataset: https://www.kaggle.com/retailrocket/ecommerce-dataset

### Outils et Frameworks
- PySpark Documentation
- Spark MLlib Guide
- MinIO Client Library
- FastAPI Tutorial

---

**Document Version**: 1.0  
**Date**: Septembre 2024  
**Auteurs**: Équipe 4IASD  
**Status**: Rapport de référence pour le projet PFA
