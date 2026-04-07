#/bin/bash
cd /tmp
rm -f arduino-cli.tar.gz arduino-cli

# descarga el tar.gz (siguiendo redirecciones)
curl -L -o arduino-cli.tar.gz \
  https://github.com/arduino/arduino-cli/releases/download/v1.4.1/arduino-cli_1.4.1_Linux_ARM64.tar.gz

# comprueba que ES un gzip de verdad
file arduino-cli.tar.gz
# debería decir algo como: gzip compressed data

# extrae SOLO el binario
tar -xzf arduino-cli.tar.gz arduino-cli

# instala en tu usuario
mkdir -p "$HOME/.local/bin"
mv arduino-cli "$HOME/.local/bin/"
chmod +x "$HOME/.local/bin/arduino-cli"

# habilita PATH (por si acaso)
grep -q '.local/bin' ~/.bashrc || echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc

arduino-cli version
which arduino-cli