start:
	docker-compose up -d
stop:
	docker-compose down
build:
	docker-compose build --no-cache
ssh:
	docker exec -it laravel-study bash
