#!/usr/bin/env bash
set -e

read -p "Username: " username
read -sp "Password: " password
echo

hashed=$(echo -n "$password" | docker exec -i zet-gtfs-admin node -e "
  const bcrypt = require('bcryptjs');
  let input = '';
  process.stdin.on('data', d => input += d);
  process.stdin.on('end', () => {
    console.log(bcrypt.hashSync(input, 10));
  });
")

docker exec zet-gtfs-postgres psql -U zetgtfs_user -d zetgtfs_admin_db -c \
  "INSERT INTO admin_users (username, password) VALUES ('$(echo "$username" | sed "s/'/''/g")', '$hashed');"

echo "Administrator '$username' created."
