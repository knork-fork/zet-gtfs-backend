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

By default, the app will be available at: `http://localhost:20000`

If the image is already built, you can simply start the container:

```bash
docker-compose up -d
```

The container will persist through system restarts.
To stop and remove the container:

```bash
docker-compose down
```

### Usage

To retrive GTFS data in json form:

```bash
curl -X GET http://localhost:20000/get_data
```