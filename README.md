# zet-gtfs-backend

### Installation

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

For frontend to work, add `.env` file to `frontend/` with the following content:

```env
VITE_API_URL=/api
```

Backend configuration should be okay as is, but you can override the default parameters via `.env.local` file.

### Usage (API)

To retrieve GTFS data in json form from backend API:

```bash
curl -X GET http://localhost:20000/api/get_data
```