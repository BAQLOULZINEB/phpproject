import argparse
import os
import sys
import random
from datetime import datetime, timedelta

import pymysql

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

spec = None
config_path = os.path.join(SCRIPT_DIR, 'config.py')
if os.path.isfile(config_path):
    import importlib.util
    spec = importlib.util.spec_from_file_location('recommender_config', config_path)
    config_mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(config_mod)
    MYSQL_HOST = config_mod.MYSQL_HOST
    MYSQL_DB = config_mod.MYSQL_DB
    MYSQL_USER = config_mod.MYSQL_USER
    MYSQL_PASSWORD = config_mod.MYSQL_PASSWORD
else:
    raise FileNotFoundError(f'Cannot locate config.py at {config_path}')

NUM_ORDERS = 40

STATUTS = ["Payée", "En attente", "Annulée"]
STATUS_WEIGHTS = [0.75, 0.20, 0.05]


def connect_db():
    return pymysql.connect(
        host=MYSQL_HOST,
        user=MYSQL_USER,
        password=MYSQL_PASSWORD,
        database=MYSQL_DB,
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor,
        autocommit=False,
    )


def get_clients(cursor):
    cursor.execute("SELECT ID FROM users WHERE role = %s", ("client",))
    return [row["ID"] for row in cursor.fetchall()]


def get_products(cursor):
    cursor.execute("SELECT id, price FROM products")
    return [(row["id"], float(row["price"])) for row in cursor.fetchall()]


def random_order_date():
    now = datetime.now()
    start = now - timedelta(days=90)
    delta_seconds = int((now - start).total_seconds())
    return start + timedelta(seconds=random.randint(0, delta_seconds))


def generate_random_orders(clients, products, num_orders, min_items, max_items, max_quantity):
    orders = []

    for _ in range(num_orders):
        user_id = random.choice(clients)
        date_commande = random_order_date()
        statut_commande = random.choices(STATUTS, weights=STATUS_WEIGHTS, k=1)[0]

        product_lines = {}
        item_count = random.randint(min_items, max_items)

        for _ in range(item_count):
            product_id, prix = random.choice(products)
            quantite = random.randint(1, max_quantity)

            if product_id in product_lines:
                product_lines[product_id]["quantite"] += quantite
            else:
                product_lines[product_id] = {
                    "product_id": product_id,
                    "quantite": quantite,
                    "prix_unitaire": prix,
                }

        orders.append({
            "user_id": user_id,
            "date_commande": date_commande,
            "statut_commande": statut_commande,
            "items": list(product_lines.values()),
        })

    return orders


def insert_orders(connection, orders):
    with connection.cursor() as cursor:
        for order in orders:
            cursor.execute(
                "INSERT INTO commande (id_client, date_commande, statut_commande) VALUES (%s, %s, %s)",
                (
                    order["user_id"],
                    order["date_commande"].strftime("%Y-%m-%d %H:%M:%S"),
                    order["statut_commande"],
                ),
            )
            order_id = cursor.lastrowid

            for item in order["items"]:
                cursor.execute(
                    "INSERT INTO ligne_commande (id_commande, id_produit, quantite, prix_unitaire) VALUES (%s, %s, %s, %s)",
                    (
                        order_id,
                        item["product_id"],
                        item["quantite"],
                        item["prix_unitaire"],
                    ),
                )

    connection.commit()


def parse_args():
    parser = argparse.ArgumentParser(description="Generate random orders for the recommender dataset.")
    parser.add_argument("--num-orders", type=int, default=NUM_ORDERS,
                        help="Number of random orders to create")
    parser.add_argument("--min-items", type=int, default=1,
                        help="Minimum number of items per order")
    parser.add_argument("--max-items", type=int, default=8,
                        help="Maximum number of items per order")
    parser.add_argument("--max-quantity", type=int, default=5,
                        help="Maximum quantity per product line")
    return parser.parse_args()


def main():
    args = parse_args()
    connection = connect_db()
    try:
        with connection.cursor() as cursor:
            clients = get_clients(cursor)
            products = get_products(cursor)

        print(f"Found {len(clients)} client users.")
        print(f"Found {len(products)} products.")

        if len(clients) < 1 or len(products) < 1:
            print("Not enough data to generate orders. Exiting without inserting.")
            return

        orders = generate_random_orders(
            clients,
            products,
            args.num_orders,
            args.min_items,
            args.max_items,
            args.max_quantity,
        )
        insert_orders(connection, orders)

        print(f"Created {len(orders)} random orders.")
    finally:
        connection.close()


if __name__ == "__main__":
    main()
