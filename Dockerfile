# syntax = docker/dockerfile:1.2

FROM node:alpine as commitlint

RUN apk add git

RUN mkdir /app

RUN cd /app

WORKDIR /app

RUN npm install @marketplacer/commitlint-config

CMD npx commitlint --from origin/HEAD --to HEAD --verbose
