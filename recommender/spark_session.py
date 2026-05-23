from pyspark.sql import SparkSession

spark = SparkSession.builder \
    .appName("ecommerce-recommender") \
    .config("spark.jars.packages", "mysql:mysql-connector-java:8.0.33") \
    .getOrCreate()
