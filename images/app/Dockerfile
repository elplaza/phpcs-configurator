FROM composer:latest

RUN rm /bin/sh && ln -s /bin/bash /bin/sh

# Cartella di lavoro
WORKDIR /app

# Vado a montare il volume dove metterò il codice
VOLUME /app

COPY entrypoint.sh /entrypoint.sh
RUN chmod a+x /entrypoint.sh
ENTRYPOINT [ "/entrypoint.sh" ]

# Comando lanciato quando si runna il container
CMD ["bash"]