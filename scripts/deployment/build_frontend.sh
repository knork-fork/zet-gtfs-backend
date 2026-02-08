#!/usr/bin/env bash
set -euo pipefail

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

UID_GID="$(id -u):$(id -g)"

if ! ( \
    docker run --rm \
        -v "$(pwd)/frontend":/app \
        -w /app \
        node:20-alpine \
        sh -c "npm ci && npm run build" ); then
    echo "Build failed."
    exit 1
fi

sudo chown -R "$UID_GID" frontend/dist frontend/node_modules

### Post-build SEO cleanup ###

FILE="frontend/dist/index.html"
TITLE='ZET tramvaji uživo u Zagrebu – karta i praćenje u stvarnom vremenu'
DESCRIPTION='Pratite ZET tramvaje i autobuse uživo na karti Zagreba u stvarnom vremenu. Dijeljenje linka na vozilo, stanice i linije.'

# Insert meta description tag
META_TAG='<meta name="description" content="Pratite ZET tramvaje i autobuse uživo na karti Zagreba. Stanice, vozni red i dijeljenje linka na vozilo.">'
if ! grep -qi '<meta[^>]*name=["'\'']description["'\'']' "$FILE"; then
  sed -i "/<meta charset=/a\\
    <meta name=\"description\" content=\"${DESCRIPTION}\">" "$FILE"
fi

# Switch title
sed -i "s|<title>.*</title>|<title>${TITLE}</title>|i" "$FILE"

# Change lang to hr
sed -i 's/<html lang="en">/<html lang="hr">/' "$FILE"

# Insert robots
sed -i "/<meta name=\"description\"/a\\
    <meta name=\"robots\" content=\"index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1\">" "$FILE"

# Insert canonical + hreflang (HR only)
sed -i "/<meta name=\"robots\"/a\\
    <link rel=\"canonical\" href=\"https://zet.knork-studio.com/\">\\
    <link rel=\"alternate\" hreflang=\"hr\" href=\"https://zet.knork-studio.com/\">" "$FILE"

# Insert Open Graph
sed -i "/<title>/a\\
    <meta property=\"og:type\" content=\"website\">\\
    <meta property=\"og:locale\" content=\"hr_HR\">\\
    <meta property=\"og:site_name\" content=\"ZET Web\">\\
    <meta property=\"og:title\" content=\"${TITLE}\">\\
    <meta property=\"og:description\" content=\"${DESCRIPTION}\">\\
    <meta property=\"og:url\" content=\"https://zet.knork-studio.com/\">\\
    <meta property=\"og:image\" content=\"https://zet.knork-studio.com/about/embed.jpg\">" "$FILE"

# Insert Twitter
sed -i "/<meta property=\"og:image\"/a\\
    <meta name=\"twitter:card\" content=\"summary_large_image\">\\
    <meta name=\"twitter:title\" content=\"${TITLE}\">\\
    <meta name=\"twitter:description\" content=\"${DESCRIPTION}\">\\
    <meta name=\"twitter:image\" content=\"https://zet.knork-studio.com/about/embed.jpg\">" "$FILE"


exit 0