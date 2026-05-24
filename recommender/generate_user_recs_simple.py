import pandas as pd
from pathlib import Path

ITEMS = Path(__file__).resolve().parent / 'data' / 'item_properties.csv'
EVENTS = Path(__file__).resolve().parent / 'data' / 'events.csv'
OUT = Path(__file__).resolve().parent / 'data' / 'user_recs.parquet'

if not ITEMS.exists() or not EVENTS.exists():
    print('Missing items or events file')
    raise SystemExit(1)

ip = pd.read_csv(ITEMS)
ev = pd.read_csv(EVENTS)

# popularity
pop = ev['item_id'].value_counts().to_dict()

# item metadata
item_cat = {int(r['item_id']): r.get('category','') for _, r in ip.iterrows()}
all_items = sorted(item_cat.keys())

# per-user interactions
user_items = ev.groupby('user_id')['item_id'].apply(list).to_dict()

rows = []
TOP_N = 10
for uid, interacted in user_items.items():
    # compute top category
    cats = [item_cat.get(i) for i in interacted if i in item_cat]
    from collections import Counter
    top_cat = Counter([c for c in cats if c]).most_common(1)
    top_cat = top_cat[0][0] if top_cat else None

    scores = []
    for item in all_items:
        if item in interacted:
            continue
        base = pop.get(item, 0)
        score = float(base)
        if top_cat and item_cat.get(item) == top_cat:
            score += 0.5 * base + 1.0
        scores.append((item, score))

    scores = sorted(scores, key=lambda x: (-x[1], x[0]))
    for rank, (item, score) in enumerate(scores[:TOP_N]):
        rows.append({'user_id': int(uid), 'item_id': int(item), 'pred_rating': float(score)})

# Also ensure users with no interactions get popular items
all_user_ids = set(user_items.keys())
popular_sorted = sorted(pop.items(), key=lambda x: (-x[1], x[0]))
popular_items = [int(x[0]) for x in popular_sorted][:TOP_N]

OUT.parent.mkdir(parents=True, exist_ok=True)
if OUT.exists():
    OUT.unlink()

if rows:
    df = pd.DataFrame(rows)
    df.to_parquet(OUT, engine='pyarrow', index=False)
    print(f'Wrote {len(df)} rows to {OUT}')
else:
    print('No rows generated')
