# Build docker environment

## Ubuntu additional preparation to run docker container

```bash
sudo curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

```bash
sudo usermod -aG docker $USER
newgrp docker
```

## Service configuration folders

- **bin** - tools to manage docker containers 
- **mysql** - mysql server container configurations
- **postgres** - pgsql server container configurations
- **nginx** - nginx server container configurations
- **php-fpm** - php-fpm server container configurations
- **php-cli** - php-cli for consumer
- **php-nv-li** - php-cli for consumer for jetson device

## Setup cert and network

- Open the `certs` folder:

```bash
cd certs
```

- Generate a self-signed certificate:

```bash
mkcert \
    -cert-file docker.localhost-cert.pem \
    -key-file docker.localhost-key.pem \
    docker.localhost "*.docker.localhost"
```

- Install the generated self-signed certificate to your OS:

```bash
mkcert -install
```

- Copy certificate to nginx configs

```bash
cp docker.localhost-cert.pem ../nginx/ssl/
cp docker.localhost-key.pem ../nginx/ssl/
```

- Go back to the root of the project:

```bash
cd ..
```

- Create `syntetiq-docker` docker network:

```bash
docker network create syntetiq-docker
```

## Build docker

- Make a copy of .env file which used for Doker startup and inside containers

```bash
cp example.env .env  
vim .env
```

Set `COMPOSE_PROJECT_NAME=sq` in `.env` to group the stack under `sq` in Docker and prefix container names like `sq-phpcli`.

- Set owner user/group id in Dockerfile

```bash
id
```

- Start docker container

```bash
docker compose up -d
```

- Stop and remove docker container

```bash
docker compose down
```

- Use ```docker-compose.override.yml``` to customize container configs


- Use `make` to control containers

```bash
make help
```

``bash
cp ../app/example.env-app.local ../app/.env-app.local
make up
make db-recreate
make composer-install
make oauth-keys-generate
make install
```

# XDEBUG

### WSL2 host

```bash
grep nameserver /etc/resolv.conf | cut -d ' ' -f2      
```

Powershell

```sh
ipconfig /all
```

```sh
New-NetFirewallRule -DisplayName “WSL” -Direction Inbound -InterfaceAlias “vEthernet (WSL (Hyper-V firewall))” -Action Allow
```

### Linux 

```bash
hostname -I | cut -d ' ' -f1
```


### Docker Nvidia GPU / RTX

https://docs.docker.com/config/containers/resource_constraints/#gpu
https://nvidia.github.io/nvidia-container-runtime/

Add to docker-compose.override.yml for consume container

```yaml
    syntetiq-phpcli: 
        deploy:
            resources:
                reservations:
                    devices:
                        - driver: nvidia
                          count: 1
                          capabilities: [gpu]
       
```

```yaml
    syntetiq-phpcli:     
        runtime: nvidia
        environment:
            NVIDIA_VISIBLE_DEVICES: all
```

to avoid run services if need to run it manual to debug

```yaml
        command: ["/usr/sbin/cron", "-f"]
```

Change GPU variable in .env file
