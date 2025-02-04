# Need to get the mkShellNoCC function
let
  nixpkgs = fetchTarball "https://github.com/NixOS/nixpkgs/tarball/nixos-24.11";
  # config and overlays are set to avoid them to being overridden by global configuration.
  pkgs = import nixpkgs { config = {}; overlays = []; };
in

# Needed to get the php 7.4.29 version (does not have mkShellNoCC)
# 7.4.32 was not available
# https://lazamar.co.uk/nix-versions/?package=php-with-extensions&version=7.4.29&fullName=php-with-extensions-7.4.29&keyName=php74&revision=6e3a86f2f73a466656a401302d3ece26fba401d9&channel=nixpkgs-unstable#instructions
let
  nixpkgsWithPhp74 = fetchTarball "https://github.com/NixOS/nixpkgs/archive/6e3a86f2f73a466656a401302d3ece26fba401d9.tar.gz";
  pkgsWithPhp74 = import nixpkgsWithPhp74 { config = {}; overlays = []; };
  php74 = pkgsWithPhp74.php74.buildEnv {
      extensions = { enabled, all }: enabled ++ (with all; [ opcache ]);
  };
in

# mkShellNoCC: a shell but without a compiler toolchain.
# https://nixos.org/manual/nixpkgs/stable/#sec-pkgs-mkShell
pkgs.mkShellNoCC {
  packages = [
    # Php package
    # https://nixos.org/manual/nixpkgs/stable/#sec-php
    # https://wiki.nixos.org/wiki/PHP
    php74
  ];
  # ShellHook: run some commands before entering the shell environment
  shellHook = ''
    echo "PHP version: $(php --version | head -n 1)"
    '';
}