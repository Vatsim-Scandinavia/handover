{
  description = "VATSIM Scandinavia Handover – Laravel dev environment with PHP 8.3/8.4/8.5, MySQL, and Node.js";

  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";

  outputs = { self, nixpkgs }: let
    inherit (nixpkgs) lib;
    forAllSystems = lib.genAttrs lib.systems.flakeExposed;
    pkgsFor = system: import nixpkgs {
      inherit system;
      config.allowUnfree = true;
    };

    phpExts = { enabled, all }: enabled ++ (with all; [
      intl
      zip
      pdo_mysql
    ]) ++ lib.optional (all ? opcache) all.opcache;

    mkPhpEnv = phpBase: phpBase.buildEnv {
      extensions = phpExts;
      extraConfig = ''
        memory_limit = 512M
        date.timezone = UTC
      '';
    };

    mkDevShell = system: let
      pkgs = pkgsFor system;
      desiredPhpVersions = [ "83" "84" "85" ];
      availablePhpVersions = builtins.filter (version: builtins.hasAttr "php${version}" pkgs) desiredPhpVersions;
      defaultPhpVersion = lib.last availablePhpVersions;
      mkPhpVersion = version: let
        phpAttr = "php${version}";
        phpEnv = mkPhpEnv pkgs.${phpAttr};
      in {
        name = "php${version}";
        env = phpEnv;
        wrapper = pkgs.writeShellScriptBin "php${version}" ''exec "${phpEnv}/bin/php" "$@"'';
      };
      phpBins = map mkPhpVersion availablePhpVersions;
      phpByName = builtins.listToAttrs (map (phpBin: {
        name = phpBin.name;
        value = phpBin;
      }) phpBins);
      defaultPhpEnv = phpByName."php${defaultPhpVersion}".env;
      defaultComposer = pkgs.${"php${defaultPhpVersion}Packages"}.composer;
      # The mise nix plugin discards shellHook output, so the banner lives in a
      # command on PATH. Run it with `mise run env-info`.
      envInfo = pkgs.writeShellScriptBin "env-info" ''
        row() { printf "  %-10s %s\n" "$1" "$2"; }
        echo ""
        echo "VATSIM Scandinavia Handover: development environment"
        echo ""
        for php in php ${lib.concatMapStringsSep " " (version: "php${version}") availablePhpVersions}; do
          row "$php" "$($php -v | head -1)"
        done
        row mysql "$(mysql --version)"
        row composer "$(composer --version 2>/dev/null)"
        row node "$(node --version)"
        row npm "$(npm --version)"
        echo ""
      '';
    in assert lib.assertMsg (availablePhpVersions != [])
      "No supported PHP versions are available in nixpkgs. Checked: ${lib.concatStringsSep ", " desiredPhpVersions}";
      pkgs.mkShell {
      name = "handover-dev";
      packages = [
        defaultPhpEnv
      ] ++ (map (phpBin: phpBin.wrapper) phpBins) ++ [
        pkgs.mariadb
        defaultComposer
        pkgs.nodejs
        envInfo
      ];

      env.PHP_PEAR_SYSCONF_DIR = "/tmp";

      shellHook = ''
        env-info
      '';
    };
  in {
    devShells = forAllSystems (system: {
      default = mkDevShell system;
    });
  };
}
