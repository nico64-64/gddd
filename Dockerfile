#FROM debian:trixie-slim
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update
RUN apt-get install --fix-missing -y openssh-server curl
RUN mkdir /var/run/sshd
RUN curl -fsSL https://get.docker.com | sh

COPY gddd /gddd
RUN chmod +x /gddd/gestionnaire.sh

RUN useradd -M -s /gddd/gestionnaire.sh -d /gddd -G docker uctf
RUN echo "uctf:2026" | chpasswd
RUN newgrp docker
RUN touch /gddd/.hushlogin
RUN echo "root:toor" | chpasswd

EXPOSE 22
CMD ["/usr/sbin/sshd", "-D"]
