<?php

declare(strict_types=1);

namespace PackageVersions;

use Composer\InstalledVersions;
use OutOfBoundsException;

class_exists(InstalledVersions::class);

/**
 * This class is generated by composer/package-versions-deprecated, specifically by
 * @see \PackageVersions\Installer
 *
 * This file is overwritten at every run of `composer install` or `composer update`.
 *
 * @deprecated in favor of the Composer\InstalledVersions class provided by Composer 2. Require composer-runtime-api:^2 to ensure it is present.
 */
final class Versions
{
    /**
     * @deprecated please use {@see self::rootPackageName()} instead.
     *             This constant will be removed in version 2.0.0.
     */
    const ROOT_PACKAGE_NAME = '__root__';

    /**
     * Array of all available composer packages.
     * Dont read this array from your calling code, but use the \PackageVersions\Versions::getVersion() method instead.
     *
     * @var array<string, string>
     * @internal
     */
    const VERSIONS          = array (
  'amphp/amp' => 'v2.6.0@caa95edeb1ca1bf7532e9118ede4a3c3126408cc',
  'amphp/byte-stream' => 'v1.8.1@acbd8002b3536485c997c4e019206b3f10ca15bd',
  'amphp/cache' => 'v1.5.0@2b6b5dbb70e54cc914df9952ba7c012bc4cbcd28',
  'amphp/dns' => 'v1.2.3@852292532294d7972c729a96b49756d781f7c59d',
  'amphp/hpack' => 'v3.1.1@cf4f1663e9fd58f60258c06177098655ca6377a5',
  'amphp/http' => 'v1.6.3@e2b75561011a9596e4574cc867e07a706d56394b',
  'amphp/http-client' => 'v4.6.1@fafc7a0e72eb103dcb54c3dbe94d2fc659e8e7e2',
  'amphp/parser' => 'v1.0.0@f83e68f03d5b8e8e0365b8792985a7f341c57ae1',
  'amphp/process' => 'v1.1.1@b88c6aef75c0b22f6f021141dd2d5e7c5db4c124',
  'amphp/serialization' => 'v1.0.0@693e77b2fb0b266c3c7d622317f881de44ae94a1',
  'amphp/socket' => 'v1.2.0@a8af9f5d0a66c5fe9567da45a51509e592788fe6',
  'amphp/sync' => 'v1.4.0@613047ac54c025aa800a9cde5b05c3add7327ed4',
  'amphp/windows-registry' => 'v0.3.3@0f56438b9197e224325e88f305346f0221df1f71',
  'composer/package-versions-deprecated' => '1.11.99.2@c6522afe5540d5fc46675043d3ed5a45a740b27c',
  'daverandom/libdns' => 'v2.0.2@e8b6d6593d18ac3a6a14666d8a68a4703b2e05f9',
  'doctrine/annotations' => '1.13.1@e6e7b7d5b45a2f2abc5460cc6396480b2b1d321f',
  'doctrine/cache' => '1.11.3@3bb5588cec00a0268829cc4a518490df6741af9d',
  'doctrine/collections' => '1.6.7@55f8b799269a1a472457bd1a41b4f379d4cfba4a',
  'doctrine/common' => '3.1.2@a036d90c303f3163b5be8b8fde9b6755b2be4a3a',
  'doctrine/dbal' => '2.13.1@c800380457948e65bbd30ba92cc17cda108bf8c9',
  'doctrine/deprecations' => 'v0.5.3@9504165960a1f83cc1480e2be1dd0a0478561314',
  'doctrine/doctrine-bundle' => '2.4.2@4202ce675d29e70a8b9ee763bec021b6f44caccb',
  'doctrine/doctrine-migrations-bundle' => '3.1.1@91f0a5e2356029575f3038432cc188b12f9d5da5',
  'doctrine/event-manager' => '1.1.1@41370af6a30faa9dc0368c4a6814d596e81aba7f',
  'doctrine/inflector' => '2.0.3@9cf661f4eb38f7c881cac67c75ea9b00bf97b210',
  'doctrine/instantiator' => '1.4.0@d56bf6102915de5702778fe20f2de3b2fe570b5b',
  'doctrine/lexer' => '1.2.1@e864bbf5904cb8f5bb334f99209b48018522f042',
  'doctrine/migrations' => '3.1.2@1c2780df6b58998f411e64973cfa464ba0a06e00',
  'doctrine/orm' => '2.9.2@75b4b88c5b7cebc24ed7251a20c2a5aa027300e1',
  'doctrine/persistence' => '2.2.1@d138f3ab5f761055cab1054070377cfd3222e368',
  'doctrine/sql-formatter' => '1.1.1@56070bebac6e77230ed7d306ad13528e60732871',
  'egulias/email-validator' => '3.1.1@c81f18a3efb941d8c4d2e025f6183b5c6d697307',
  'ezyang/htmlpurifier' => 'v4.13.0@08e27c97e4c6ed02f37c5b2b20488046c8d90d75',
  'friendsofphp/proxy-manager-lts' => 'v1.0.5@006aa5d32f887a4db4353b13b5b5095613e0611f',
  'kelunik/certificate' => 'v1.1.2@56542e62d51533d04d0a9713261fea546bff80f6',
  'knplabs/knp-components' => 'v3.1.0@f06a6ebea71a91c8cd34213f9417004062133627',
  'knplabs/knp-paginator-bundle' => 'v5.6.0@a11cd180826e9475e1079b3b64c27a1c33c48917',
  'laminas/laminas-code' => '4.3.0@1beb4447f9efd26041eba7eff50614e798c353fd',
  'laminas/laminas-eventmanager' => '3.3.1@966c859b67867b179fde1eff0cd38df51472ce4a',
  'laminas/laminas-zendframework-bridge' => '1.2.0@6cccbddfcfc742eb02158d6137ca5687d92cee32',
  'league/uri' => '6.4.0@09da64118eaf4c5d52f9923a1e6a5be1da52fd9a',
  'league/uri-interfaces' => '2.3.0@00e7e2943f76d8cb50c7dfdc2f6dee356e15e383',
  'league/uri-parser' => '1.4.1@671548427e4c932352d9b9279fdfa345bf63fa00',
  'maennchen/zipstream-php' => '2.1.0@c4c5803cc1f93df3d2448478ef79394a5981cc58',
  'markbaker/complex' => '2.0.3@6f724d7e04606fd8adaa4e3bb381c3e9db09c946',
  'markbaker/matrix' => '2.1.3@174395a901b5ba0925f1d790fa91bab531074b61',
  'monolog/monolog' => '2.2.0@1cb1cde8e8dd0f70cc0fe51354a59acad9302084',
  'myclabs/php-enum' => '1.8.0@46cf3d8498b095bd33727b13fd5707263af99421',
  'phpdocumentor/reflection-common' => '2.2.0@1d01c49d4ed62f25aa84a747ad35d5a16924662b',
  'phpdocumentor/reflection-docblock' => '5.2.2@069a785b2141f5bcf49f3e353548dc1cce6df556',
  'phpdocumentor/type-resolver' => '1.4.0@6a467b8989322d92aa1c8bf2bebcc6e5c2ba55c0',
  'phpoffice/phpspreadsheet' => '1.18.0@418cd304e8e6b417ea79c3b29126a25dc4b1170c',
  'psr/cache' => '1.0.1@d11b50ad223250cf17b86e38383413f5a6764bf8',
  'psr/container' => '1.1.1@8622567409010282b7aeebe4bb841fe98b58dcaf',
  'psr/event-dispatcher' => '1.0.0@dbefd12671e8a14ec7f180cab83036ed26714bb0',
  'psr/http-client' => '1.0.1@2dfb5f6c5eff0e91e20e913f8c5452ed95b86621',
  'psr/http-factory' => '1.0.1@12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
  'psr/http-message' => '1.0.1@f6561bf28d520154e4b0ec72be95418abe6d9363',
  'psr/link' => '1.0.0@eea8e8662d5cd3ae4517c9b864493f59fca95562',
  'psr/log' => '1.1.4@d49695b909c3b7628b6289db5479a1c204601f11',
  'psr/simple-cache' => '1.0.1@408d5eafb83c57f6365a3ca330ff23aa4a5fa39b',
  'sensio/framework-extra-bundle' => 'v6.1.5@62c5909f49cf74dccdf50a294511cc24be2f969c',
  'symfony/apache-pack' => 'v1.0.1@3aa5818d73ad2551281fc58a75afd9ca82622e6c',
  'symfony/asset' => 'v5.3.0@4c8d354b8931788f2b07953cfe6846e5cda27637',
  'symfony/cache' => 'v5.3.0@44fd0f97d1fb198d344f22379dfc56af2221e608',
  'symfony/cache-contracts' => 'v2.4.0@c0446463729b89dd4fa62e9aeecc80287323615d',
  'symfony/config' => 'v5.3.0@9f4a448c2d7fd2c90882dfff930b627ddbe16810',
  'symfony/console' => 'v5.3.0@058553870f7809087fa80fa734704a21b9bcaeb2',
  'symfony/dependency-injection' => 'v5.3.0@94d973cb742d8c5c5dcf9534220e6b73b09af1d4',
  'symfony/deprecation-contracts' => 'v2.4.0@5f38c8804a9e97d23e0c8d63341088cd8a22d627',
  'symfony/doctrine-bridge' => 'v5.3.1@29516dcc8165bc7e2339182a9360ea7d3471fb03',
  'symfony/dotenv' => 'v5.3.0@1ac423fcc9548709077f90aca26c733cdb7e6e5c',
  'symfony/error-handler' => 'v5.3.0@0e6768b8c0dcef26df087df2bbbaa143867a59b2',
  'symfony/event-dispatcher' => 'v5.3.0@67a5f354afa8e2f231081b3fa11a5912f933c3ce',
  'symfony/event-dispatcher-contracts' => 'v2.4.0@69fee1ad2332a7cbab3aca13591953da9cdb7a11',
  'symfony/expression-language' => 'v5.3.0@e3c136ac5333b0d2ca9de9e7e3efe419362aa046',
  'symfony/filesystem' => 'v5.3.0@348116319d7fb7d1faa781d26a48922428013eb2',
  'symfony/finder' => 'v5.3.0@0ae3f047bed4edff6fd35b26a9a6bfdc92c953c6',
  'symfony/flex' => 'v1.13.3@2597d0dda8042c43eed44a9cd07236b897e427d7',
  'symfony/form' => 'v5.3.0@3f196ab4e1f0379a045c7d6f5eddc07417e9933e',
  'symfony/framework-bundle' => 'v5.3.0@99196372c703b8cc97ee61d63d98acbf9896d425',
  'symfony/http-client' => 'v5.3.0@ef85ca5fa7a4f9c57592fab49faeccdf22b13136',
  'symfony/http-client-contracts' => 'v2.4.0@7e82f6084d7cae521a75ef2cb5c9457bbda785f4',
  'symfony/http-foundation' => 'v5.3.1@8827b90cf8806e467124ad476acd15216c2fceb6',
  'symfony/http-kernel' => 'v5.3.1@74eb022e3bac36b3d3a897951a98759f2b32b864',
  'symfony/intl' => 'v5.3.0@92a24a5fd1087511d29a5c7dd98d97c9e2208e75',
  'symfony/mailer' => 'v5.3.0@3b7f45a1f5488032da33c00f619909b4a6bf57d6',
  'symfony/mime' => 'v5.3.0@ed710d297b181f6a7194d8172c9c2423d58e4852',
  'symfony/monolog-bridge' => 'v5.3.0@84841557874df015ef2843aa16ac63d09f97c7b9',
  'symfony/monolog-bundle' => 'v3.7.0@4054b2e940a25195ae15f0a49ab0c51718922eb4',
  'symfony/notifier' => 'v5.3.0@9272976b845ace9b74d60d20b2f655a3c23bd1ef',
  'symfony/options-resolver' => 'v5.3.0@162e886ca035869866d233a2bfef70cc28f9bbe5',
  'symfony/password-hasher' => 'v5.3.0@d487faef0347d5351d3e361e123a73496595509f',
  'symfony/polyfill-intl-grapheme' => 'v1.23.0@24b72c6baa32c746a4d0840147c9715e42bb68ab',
  'symfony/polyfill-intl-icu' => 'v1.23.0@4a80a521d6176870b6445cfb469c130f9cae1dda',
  'symfony/polyfill-intl-idn' => 'v1.23.0@65bd267525e82759e7d8c4e8ceea44f398838e65',
  'symfony/polyfill-intl-normalizer' => 'v1.23.0@8590a5f561694770bdcd3f9b5c69dde6945028e8',
  'symfony/polyfill-mbstring' => 'v1.23.0@2df51500adbaebdc4c38dea4c89a2e131c45c8a1',
  'symfony/polyfill-php73' => 'v1.23.0@fba8933c384d6476ab14fb7b8526e5287ca7e010',
  'symfony/polyfill-php80' => 'v1.23.0@eca0bf41ed421bed1b57c4958bab16aa86b757d0',
  'symfony/polyfill-php81' => 'v1.23.0@e66119f3de95efc359483f810c4c3e6436279436',
  'symfony/process' => 'v5.3.0@53e36cb1c160505cdaf1ef201501669c4c317191',
  'symfony/property-access' => 'v5.3.0@8988399a556cffb0fba9bb3603f8d1ba4543eceb',
  'symfony/property-info' => 'v5.3.1@6f8bff281f215dbf41929c7ec6f8309cdc0912cf',
  'symfony/proxy-manager-bridge' => 'v5.3.0@4e4997e77f30b4caed2b3cebefdecd7031e25a00',
  'symfony/requirements-checker' => 'v2.0.0@e3d5565eb69a4a2195905c8669f32e988c8e6be0',
  'symfony/routing' => 'v5.3.0@368e81376a8e049c37cb80ae87dbfbf411279199',
  'symfony/runtime' => 'v5.3.0@d119a381b76eb39c5c4f4ee284cb1dd431109c7c',
  'symfony/security-bundle' => 'v5.3.0@c822d1ca612da832daad4be883bdc50f03127ad8',
  'symfony/security-core' => 'v5.3.1@22714af1f701937a0a0bd3e3ec2a761baed3f2d0',
  'symfony/security-csrf' => 'v5.3.0@c7b7006d3ed955da978a002d764cae388bed8d09',
  'symfony/security-guard' => 'v5.3.0@07b97d3c8fa9b761ad3a52d659a47661b458c51b',
  'symfony/security-http' => 'v5.3.1@195f7207dec4519ca8b0e407ba921ec239aa5cee',
  'symfony/serializer' => 'v5.3.1@ebb3dc397af77a08d734eea7305e0b2ec8c5e875',
  'symfony/service-contracts' => 'v2.4.0@f040a30e04b57fbcc9c6cbcf4dbaa96bd318b9bb',
  'symfony/stopwatch' => 'v5.3.0@313d02f59d6543311865007e5ff4ace05b35ee65',
  'symfony/string' => 'v5.3.0@a9a0f8b6aafc5d2d1c116dcccd1573a95153515b',
  'symfony/translation' => 'v5.3.0@251de0d921c42ef0a81494d8f37405421deefdf6',
  'symfony/translation-contracts' => 'v2.4.0@95c812666f3e91db75385749fe219c5e494c7f95',
  'symfony/twig-bridge' => 'v5.3.0@cbd8f87a3d2445e566db3fe75e34a0bcad70c222',
  'symfony/twig-bundle' => 'v5.3.0@d386aaa46d1afe5afb51b39675fc2ab206159206',
  'symfony/validator' => 'v5.3.1@111e71ac585a47358e808bc687dcaf66e568470a',
  'symfony/var-dumper' => 'v5.3.0@1d3953e627fe4b5f6df503f356b6545ada6351f3',
  'symfony/var-exporter' => 'v5.3.0@7a7c9dd972541f78e7815c03c0bae9f81e0e9dbb',
  'symfony/web-link' => 'v5.3.0@87b34e33e833e948256b237f25fdd54f85975a94',
  'symfony/yaml' => 'v5.3.0@3bbcf262fceb3d8f48175302e6ba0ac96e3a5a11',
  'symfonycasts/reset-password-bundle' => 'v1.9.1@775e847613737f55cf06c6a7457f4bcf8ce258e5',
  'twig/extra-bundle' => 'v3.3.1@e12a8ee63387abb83fb7e4c897663bfb94ac22b6',
  'twig/twig' => 'v3.3.2@21578f00e83d4a82ecfa3d50752b609f13de6790',
  'webmozart/assert' => '1.10.0@6964c76c7804814a842473e0c8fd15bab0f18e25',
  'myclabs/deep-copy' => '1.10.2@776f831124e9c62e1a2c601ecc52e776d8bb7220',
  'nikic/php-parser' => 'v4.10.5@4432ba399e47c66624bc73c8c0f811e5c109576f',
  'phar-io/manifest' => '2.0.1@85265efd3af7ba3ca4b2a2c34dbfc5788dd29133',
  'phar-io/version' => '3.1.0@bae7c545bef187884426f042434e561ab1ddb182',
  'phpspec/prophecy' => '1.13.0@be1996ed8adc35c3fd795488a653f4b518be70ea',
  'phpunit/php-code-coverage' => '9.2.6@f6293e1b30a2354e8428e004689671b83871edde',
  'phpunit/php-file-iterator' => '3.0.5@aa4be8575f26070b100fccb67faabb28f21f66f8',
  'phpunit/php-invoker' => '3.1.1@5a10147d0aaf65b58940a0b72f71c9ac0423cc67',
  'phpunit/php-text-template' => '2.0.4@5da5f67fc95621df9ff4c4e5a84d6a8a2acf7c28',
  'phpunit/php-timer' => '5.0.3@5a63ce20ed1b5bf577850e2c4e87f4aa902afbd2',
  'phpunit/phpunit' => '9.5.5@89ff45ea9d70e35522fb6654a2ebc221158de276',
  'sebastian/cli-parser' => '1.0.1@442e7c7e687e42adc03470c7b668bc4b2402c0b2',
  'sebastian/code-unit' => '1.0.8@1fc9f64c0927627ef78ba436c9b17d967e68e120',
  'sebastian/code-unit-reverse-lookup' => '2.0.3@ac91f01ccec49fb77bdc6fd1e548bc70f7faa3e5',
  'sebastian/comparator' => '4.0.6@55f4261989e546dc112258c7a75935a81a7ce382',
  'sebastian/complexity' => '2.0.2@739b35e53379900cc9ac327b2147867b8b6efd88',
  'sebastian/diff' => '4.0.4@3461e3fccc7cfdfc2720be910d3bd73c69be590d',
  'sebastian/environment' => '5.1.3@388b6ced16caa751030f6a69e588299fa09200ac',
  'sebastian/exporter' => '4.0.3@d89cc98761b8cb5a1a235a6b703ae50d34080e65',
  'sebastian/global-state' => '5.0.2@a90ccbddffa067b51f574dea6eb25d5680839455',
  'sebastian/lines-of-code' => '1.0.3@c1c2e997aa3146983ed888ad08b15470a2e22ecc',
  'sebastian/object-enumerator' => '4.0.4@5c9eeac41b290a3712d88851518825ad78f45c71',
  'sebastian/object-reflector' => '2.0.4@b4f479ebdbf63ac605d183ece17d8d7fe49c15c7',
  'sebastian/recursion-context' => '4.0.4@cd9d8cf3c5804de4341c283ed787f099f5506172',
  'sebastian/resource-operations' => '3.0.3@0f4443cb3a1d92ce809899753bc0d5d5a8dd19a8',
  'sebastian/type' => '2.3.2@0d1c587401514d17e8f9258a27e23527cb1b06c1',
  'sebastian/version' => '3.0.2@c6c1022351a901512170118436c764e473f6de8c',
  'symfony/browser-kit' => 'v5.3.0@379984e25eee9811b0a25a2105e1a2b3b8d9b734',
  'symfony/css-selector' => 'v5.3.0@fcd0b29a7a0b1bb5bfbedc6231583d77fea04814',
  'symfony/debug-bundle' => 'v5.3.0@b73833ac97189fc809816dfbb02185a1a793b072',
  'symfony/dom-crawler' => 'v5.3.0@55fff62b19f413f897a752488ade1bc9c8a19cdd',
  'symfony/maker-bundle' => 'v1.31.1@4f57a44cef0b4555043160b8bf223fcde8a7a59a',
  'symfony/phpunit-bridge' => 'v5.3.0@15cab721487b7bf43ad545a1e7d0095782e26f8c',
  'symfony/web-profiler-bundle' => 'v5.3.0@275350559a4817881419ac959ceb3b308199fcf2',
  'theseer/tokenizer' => '1.2.0@75a63c33a8577608444246075ea0af0d052e452a',
  'symfony/polyfill-ctype' => '*@b59d7c49b1f931001b51dd746b6275c0a57990f7',
  'symfony/polyfill-iconv' => '*@b59d7c49b1f931001b51dd746b6275c0a57990f7',
  'symfony/polyfill-php72' => '*@b59d7c49b1f931001b51dd746b6275c0a57990f7',
  '__root__' => 'dev-master@b59d7c49b1f931001b51dd746b6275c0a57990f7',
);

    private function __construct()
    {
    }

    /**
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function rootPackageName() : string
    {
        if (!class_exists(InstalledVersions::class, false) || !(method_exists(InstalledVersions::class, 'getAllRawData') ? InstalledVersions::getAllRawData() : InstalledVersions::getRawData())) {
            return self::ROOT_PACKAGE_NAME;
        }

        return InstalledVersions::getRootPackage()['name'];
    }

    /**
     * @throws OutOfBoundsException If a version cannot be located.
     *
     * @psalm-param key-of<self::VERSIONS> $packageName
     * @psalm-pure
     *
     * @psalm-suppress ImpureMethodCall we know that {@see InstalledVersions} interaction does not
     *                                  cause any side effects here.
     */
    public static function getVersion(string $packageName): string
    {
        if (class_exists(InstalledVersions::class, false) && (method_exists(InstalledVersions::class, 'getAllRawData') ? InstalledVersions::getAllRawData() : InstalledVersions::getRawData())) {
            return InstalledVersions::getPrettyVersion($packageName)
                . '@' . InstalledVersions::getReference($packageName);
        }

        if (isset(self::VERSIONS[$packageName])) {
            return self::VERSIONS[$packageName];
        }

        throw new OutOfBoundsException(
            'Required package "' . $packageName . '" is not installed: check your ./vendor/composer/installed.json and/or ./composer.lock files'
        );
    }
}
