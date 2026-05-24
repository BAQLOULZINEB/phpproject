# 🚀 Quick Start Guide - Glow-E Recommender System

## One-Time Setup

### 1. Install Dependencies
```bash
cd "c:\xampp\htdocs\Glow-E.web .1.0.1"
# Install scipy and numpy for ALS training
pip install scipy numpy
```

---

## Running the Complete System

### Step 1: Train Recommendations (30 seconds)
```bash
python recommender/train_implicit_als.py
```
**Output**: 
- Generates `recommender/data/user_recs.parquet`
- Shows: 16 unique top-5 lists ✓
- Shows: MSE convergence to ~0.45

### Step 2: Start FastAPI Backend
```bash
python recommender/api/main.py
```
**Output**:
```
Uvicorn running on http://0.0.0.0:8000
```
**Verify**: Open http://localhost:8000/health in browser

### Step 3: Launch Streamlit Dashboard
```bash
streamlit run recommender/dashboard_app.py
```
**Output**:
```
You can now view your Streamlit app in your browser.
Local URL: http://localhost:8501
```

---

## Using the Dashboard

### Main Pages
1. **Aperçu du dataset** - Data overview, statistics
2. **Comportement utilisateur** - User interaction patterns
3. **Performance du modèle** - Model metrics
4. **Démo recommandations** - Live recommendations demo
5. **🔧 Debug & Diagnostics** - NEW: Inspect system internals

### Recommendation Demo
1. Go to "Démo recommandations" tab
2. Select **User ID** (2-24 available)
3. Adjust **K** (number of recommendations): 3-20
4. Click **🔄 Rafraîchir** to reload
5. View **personalized recommendations** with images

### Debug/Diagnostics
1. Go to "🔧 Debug & Diagnostics" tab
2. View **API state** (users, products, popular items)
3. Select a user to see:
   - Past interactions
   - Preferred category
   - Detailed candidate scores with re-ranking info
4. View **diversity metrics** (unique top-5 lists, score distribution)
5. Check **API health** status

---

## Testing Endpoints Manually

### Get Recommendations
```bash
# User 16, get 5 recommendations
curl "http://localhost:8000/recommend/16?top_n=5"
```
**Response**:
```json
{
  "user_id": 16,
  "recommendations": [
    {
      "id": 73,
      "nom": "Product Name",
      "prix": 49.99,
      "categorie": "Foundation",
      "image_url": "..."
    },
    ...
  ],
  "source": "model"
}
```

### Debug Detailed Scores
```bash
curl "http://localhost:8000/debug/recommend/16"
```
**Shows**: Base scores, category matches, interaction history

### System Health
```bash
curl "http://localhost:8000/health"
```

---

## Verifying Everything Works

### ✅ Checklist
- [ ] `train_implicit_als.py` runs without errors (MSE converges)
- [ ] `user_recs.parquet` created with 160 rows (16 users × 10 items)
- [ ] API starts on http://localhost:8000
- [ ] Dashboard accessible on http://localhost:8501
- [ ] "Démo recommandations" shows different items for different users
- [ ] "🔧 Debug & Diagnostics" page displays without errors
- [ ] API `/health` and `/debug/state` endpoints respond

### 🧪 Quick Test
```python
# Run in Python console
import requests
resp = requests.get('http://localhost:8000/recommend/16?top_n=5')
print(resp.json()['recommendations'])
# Should show 5 unique items personalized for user 16
```

---

## Troubleshooting

### "PySpark not found" error
**Status**: ✓ EXPECTED (Spark ALS path skipped)  
**Solution**: System uses Python-native implicit ALS instead (faster, no Java required)

### "No recommendations for user X"
**Cause**: User not in training data (only users 2-24 available)  
**Solution**: Select a user ID between 2-24

### Dashboard shows "API unavailable"
**Cause**: FastAPI server not running  
**Solution**: Run `python recommender/api/main.py` in separate terminal

### Images not showing
**Cause**: Local product images not found  
**Solution**: Images should be in `images/prod_images/` folder (already exists)

### Recommendations identical for all users
**Status**: ✓ FIXED (was the original problem)  
**Verify**: Run `python recommender/train_implicit_als.py` to regenerate

---

## File Structure
```
Glow-E.web .1.0.1/
├── recommender/
│   ├── api/
│   │   ├── main.py                      (FastAPI backend - MODIFIED)
│   ├── data/
│   │   ├── events.csv                   (interaction history)
│   │   ├── item_properties.csv          (product metadata)
│   │   ├── user_recs.parquet            (model outputs - REGENERATED)
│   │   └── als_model/                   (saved model files)
│   ├── train_als.py                     (Spark training - MODIFIED)
│   ├── train_implicit_als.py            (NEW: Python-native ALS)
│   ├── generate_user_recs_simple.py     (fallback generator)
│   └── dashboard_app.py                 (Streamlit UI - ENHANCED)
├── images/
│   └── prod_images/                     (product images for display)
└── RECOMMENDER_UPGRADE_SUMMARY.md       (full documentation)
```

---

## Performance Expectations

- **Training time**: ~30 seconds
- **API response time**: <200ms per recommendation request
- **Dashboard load time**: <2 seconds
- **Recommendation diversity**: 100% (16 unique top-5 lists for 16 users)

---

## Key Metrics to Monitor

1. **Diversity**: Check "🔧 Debug & Diagnostics" → "Unique top-5 lists" (should be 16)
2. **Coverage**: All users should get top_n items (checked in debug endpoint)
3. **Latency**: API `/health` response time
4. **Re-ranking impact**: Compare "base_score" vs final order in debug output

---

*For detailed technical documentation, see `RECOMMENDER_UPGRADE_SUMMARY.md`*
