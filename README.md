# Live ZET GTFS

Live ZET GTFS data presented on a leaflet map.

Data is polled periodically (default: every 5 minutes) from official ZET API (https://www.zet.hr/odredbe/datoteke-u-gtfs-formatu/669).

As a good will mechanism, if no activity is detected for certain time (default: 10 minutes) the app will stop polling the API until activity is detected again.

### Installation

After cloning the repo, do:

```bash
git submodule update --init --recursive
```
to clone the frontend repository.

Then, add `.env` file to `frontend/` with the following content:

```env
VITE_API_URL=/api
```

Start the container and build the image:

```bash
docker-compose up --build -d
```

Install dependencies using Composer:

```bash
docker/composer install --no-interaction
```

By default, the app (frontend) will be available at: `http://localhost:20000`

If the image is already built, you can simply start the container:

```bash
docker-compose up -d
```

The container will persist through system restarts.
To stop and remove the container:

```bash
docker-compose down
```

### Configuration

Backend configuration should be okay as is, but you can override the default parameters via `.env.local` file.

### Usage (API)

To retrieve GTFS data in json form from backend API:

```bash
curl -X GET http://localhost:20000/api/get_data
```

### Updates

Run the following command to update deployed version to latest repo state:

```bash
scripts/update_from_repo.sh
```