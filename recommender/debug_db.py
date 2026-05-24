import importlib.util
import json
import pymysql

spec = importlib.util.spec_from_file_location('cfg', 'config.py')
cfg = importlib.util.module_from_spec(spec)
spec.loader.exec_module(cfg)

conn = pymysql.connect(host=cfg.MYSQL_HOST, user=cfg.MYSQL_USER, password=cfg.MYSQL_PASSWORD, database=cfg.MYSQL_DB, cursorclass=pymysql.cursors.DictCursor)
with conn.cursor() as cur:
    cur.execute("SELECT c.id_commande,c.id_client,c.date_commande,lc.id_produit,lc.quantite,lc.prix_unitaire FROM commande c JOIN ligne_commande lc ON c.id_commande=lc.id_commande LIMIT 5")
    rows = cur.fetchall()
    print(json.dumps(rows, default=str, ensure_ascii=False, indent=2))
conn.close()
