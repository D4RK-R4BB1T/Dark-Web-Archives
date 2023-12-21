FROM debian:10-slim
LABEL maintainer="dev@null.local"

RUN apt-get update && \
	apt-get install --yes make wget

RUN adduser --disabled-login --gecos "" bitcoin

WORKDIR /home/bitcoin

ENV BITCOIN_CORE_VERSION "0.21.0"

# download and install bitcoin binaries
RUN mkdir tmp \
	&& cd tmp \
	&& wget "https://bitcoincore.org/bin/bitcoin-core-${BITCOIN_CORE_VERSION}/bitcoin-${BITCOIN_CORE_VERSION}-x86_64-linux-gnu.tar.gz" \
	&& tar xzf "bitcoin-${BITCOIN_CORE_VERSION}-x86_64-linux-gnu.tar.gz" \
	&& cd "bitcoin-${BITCOIN_CORE_VERSION}/bin" \
	&& install --mode 755 --target-directory /usr/local/bin *

# clean up
RUN rm -r tmp

# copy the testnet-box files into the image
ADD . /home/tester/bitcoin-testnet-box

# make tester user own the bitcoin-testnet-box
RUN chown -R tester:tester /home/tester/bitcoin-testnet-box

# color PS1
RUN mv /home/tester/bitcoin-testnet-box/.bashrc /home/tester/ && \
	cat /home/tester/.bashrc >> /etc/bash.bashrc

# use the tester user when running the image
USER tester

# run commands from inside the testnet-box directory
WORKDIR /home/tester/bitcoin-testnet-box

# expose two rpc ports for the nodes to allow outside container access
EXPOSE 19001 19011
CMD ["/bin/bash"]
