FROM mhart/alpine-node

COPY . /app

WORKDIR /app

RUN yarn install --frozen-lockfile && yarn && yarn build

EXPOSE 3000

CMD npm start
