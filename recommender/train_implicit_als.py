"""
Lightweight implicit ALS training using NumPy and SciPy.
This produces personalized recommendations without external ML frameworks.
"""
import pandas as pd
import numpy as np
from pathlib import Path
from scipy.sparse import csr_matrix
import warnings
warnings.filterwarnings('ignore')

def build_rating_matrix(events_path, items_path):
    """
    Build a sparse (user, item) rating matrix from events, weighted by event type.
    """
    ev = pd.read_csv(events_path)
    ip = pd.read_csv(items_path)
    
    # Map event types to weights
    event_weights = {
        'purchase': 5.0,
        'add_to_cart': 4.0,
        'checkout': 4.0,
        'view': 1.0,
        'click': 1.0,
    }
    
    ev['weight'] = ev['event_type'].map(event_weights).fillna(1.0)
    
    # Aggregate by (user, item)
    agg = ev.groupby(['user_id', 'item_id'])['weight'].sum().reset_index()
    agg.columns = ['user_id', 'item_id', 'rating']
    
    # Filter to valid items
    valid_items = set(ip['item_id'].astype(int))
    agg = agg[agg['item_id'].isin(valid_items)]
    
    # Build mapping
    user_map = {uid: i for i, uid in enumerate(sorted(agg['user_id'].unique()))}
    item_map = {iid: i for i, iid in enumerate(sorted(agg['item_id'].unique()))}
    
    n_users = len(user_map)
    n_items = len(item_map)
    
    rows = [user_map[int(u)] for u in agg['user_id']]
    cols = [item_map[int(i)] for i in agg['item_id']]
    vals = agg['rating'].values
    
    R = csr_matrix((vals, (rows, cols)), shape=(n_users, n_items), dtype=np.float32)
    
    print(f"Rating matrix: {R.shape}, sparsity={1 - R.nnz / (n_users * n_items):.2%}")
    print(f"Users: {n_users}, Items: {n_items}, Interactions: {R.nnz}")
    
    return R, user_map, item_map


def implicit_als_iteration(R, U, V, lambda_reg=0.1):
    """
    One ALS iteration: fix V, solve for U; then fix U, solve for V.
    """
    m, n = R.shape
    rank = U.shape[1]
    
    # Solve for U (each user)
    for i in range(m):
        V_T_V = V.T @ V + lambda_reg * np.eye(rank)
        V_T_r = V.T @ R[i].toarray().ravel()
        try:
            U[i] = np.linalg.solve(V_T_V, V_T_r)
        except np.linalg.LinAlgError:
            pass
    
    # Solve for V (each item)
    for j in range(n):
        U_T_U = U.T @ U + lambda_reg * np.eye(rank)
        U_T_r = U.T @ R[:, j].toarray().ravel()
        try:
            V[j] = np.linalg.solve(U_T_U, U_T_r)
        except np.linalg.LinAlgError:
            pass
    
    return U, V


def train_implicit_als(R, rank=10, n_iter=15, lambda_reg=0.1):
    """
    Train implicit ALS factorization.
    """
    m, n = R.shape
    
    # Initialize
    np.random.seed(42)
    U = np.random.randn(m, rank) * 0.01
    V = np.random.randn(n, rank) * 0.01
    
    print(f"Starting ALS: rank={rank}, iterations={n_iter}, lambda={lambda_reg}")
    
    for it in range(n_iter):
        U, V = implicit_als_iteration(R, U, V, lambda_reg)
        
        # Compute reconstruction error on sampled interactions
        pred = U @ V.T
        mask = (R > 0).toarray()
        mse = np.sum((R.toarray() - pred) ** 2 * mask) / R.nnz if R.nnz > 0 else 0
        print(f"  Iteration {it+1}/{n_iter}: MSE={mse:.4f}")
    
    return U, V


def generate_recommendations(U, V, user_map, item_map, top_n=10):
    """
    Generate top-N recommendations for each user.
    """
    pred = U @ V.T
    rows = []
    
    for user_id, u_idx in user_map.items():
        scores = pred[u_idx]
        top_items = np.argsort(-scores)[:top_n]
        
        for rank, item_idx in enumerate(top_items):
            item_id = list(item_map.keys())[list(item_map.values()).index(item_idx)]
            score = float(scores[item_idx])
            rows.append({
                'user_id': int(user_id),
                'item_id': int(item_id),
                'pred_rating': score
            })
    
    return pd.DataFrame(rows)


def main():
    events_file = Path(__file__).resolve().parent / 'data' / 'events.csv'
    items_file = Path(__file__).resolve().parent / 'data' / 'item_properties.csv'
    output_file = Path(__file__).resolve().parent / 'data' / 'user_recs.parquet'
    
    if not events_file.exists() or not items_file.exists():
        print(f"ERROR: Missing files. Looked for:")
        print(f"  {events_file}")
        print(f"  {items_file}")
        return
    
    print("Building rating matrix...")
    R, user_map, item_map = build_rating_matrix(events_file, items_file)
    
    print("\nTraining implicit ALS...")
    U, V = train_implicit_als(R, rank=15, n_iter=20, lambda_reg=0.05)
    
    print("\nGenerating recommendations...")
    recs_df = generate_recommendations(U, V, user_map, item_map, top_n=10)
    
    output_file.parent.mkdir(parents=True, exist_ok=True)
    if output_file.exists():
        output_file.unlink()
    
    recs_df.to_parquet(output_file, engine='pyarrow', index=False)
    print(f"\nWrote {len(recs_df)} recommendations to {output_file}")
    
    # Verify diversity
    top5_per_user = recs_df.sort_values('pred_rating', ascending=False).groupby('user_id').head(5).groupby('user_id')['item_id'].apply(tuple)
    unique_top5 = top5_per_user.nunique()
    print(f"Unique top-5 lists: {unique_top5} / {len(top5_per_user)}")


if __name__ == '__main__':
    main()
