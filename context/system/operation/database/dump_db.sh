# Dump smartcore database structure V1.0 (c) Paragi, Simon Riget 2016
#
# To reload database use: psql -U {user-name} -d {desintation_db}-f {dumpfilename.sql}

FILE=DBregenerate.sql
PG_OPTIONS="--username=postgres --clean --create --no-owner --schema=public --no-privileges --schema-only --if-exists"

sudo su postgres -c "pg_dump ${PG_OPTIONS} smartcore" > $FILE
sudo chown www-data:www-data $FILE

