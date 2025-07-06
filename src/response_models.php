<?php

declare(strict_types=1);

namespace Dymo\Models;

enum VerifyPlugins: string {
    case COMPROMISE_DETECTOR = "compromiseDetector";
    case NSFW = "nsfw";
    case REACHABLE = "reachable";
    case REPUTATION = "reputation";
    case TOR_NETWORK = "torNetwork";
    case TYPOSQUATTING = "typosquatting";
    case URL_SHORTENER = "urlShortener";
    case BLOCKLIST = "blocklist";
}

class PhoneData {
    public $iso;
    public string $phone;

    public function __construct($iso, string $phone) {
        $this->iso = $iso;
        $this->phone = $phone;
    }
}

class CreditCardData {
    public string|int $pan;
    public ?string $expirationDate;
    public string|int $cvc;
    public string|int $cvv;

    public function __construct(string|int $pan, string $expirationDate, string|int $cvc, string|int $cvv) {
        $this->pan = $pan;
        $this->expirationDate = $expirationDate;
        $this->cvc = $cvc;
        $this->cvv = $cvv;
    }
}

class Validator {
    public ?string $email;
    public PhoneData|string|null $phone;
    public ?string $domain;
    public ?string $creditCard;
    public ?CreditCardData $creditCardData;
    public ?string $ip;
    public ?string $wallet;
    public ?string $userAgent;
    public ?array $plugins;

    public function __construct(
        ?string $email,
        ?PhoneData $phone,
        ?string $domain,
        ?string $creditCard,
        ?CreditCardData $creditCardData,
        ?string $ip,
        ?string $wallet,
        ?string $userAgent,
        ?array $plugins
    ) {
        $this->email = $email;
        $this->phone = $phone;
        $this->domain = $domain;
        $this->creditCard = $creditCard;
        $this->creditCardData = $creditCardData;
        $this->ip = $ip;
        $this->wallet = $wallet;
        $this->userAgent = $userAgent;
        if ($plugins) $this->setPlugins($plugins);
    }

    // Method to set and validate the 'plugins' array using enum.
    public function setPlugins(array $plugins) {
        foreach ($plugins as $plugin) {
            if (!VerifyPlugins::tryFrom($plugin)) throw new InvalidArgumentException("Invalid plugin: $plugin");
        }
        $this->plugins = $plugins;
    }
}

class UrlEncryptResponse {
    public string $original;
    public string $code;
    public string $encrypt;

    public function __construct(string $original, string $code, string $encrypt) {
        $this->original = $original;
        $this->code = $code;
        $this->encrypt = $encrypt;
    }
}

class IsValidPwdData {
    public ?string $email;
    public ?string $password;
    public string|array $bannedWords;
    public ?int $min;
    public ?int $max;

    public function __construct(
        ?string $email,
        ?string $password,
        string|array $bannedWords,
        ?int $min,
        ?int $max
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->bannedWords = $bannedWords;
        $this->min = $min;
        $this->max = $max;
    }
}

class IsValidPwdDetails {
    public string $validation;
    public string $message;

    public function __construct(string $validation, string $message) {
        $this->validation = $validation;
        $this->message = $message;
    }
}

class IsValidPwdResponse {
    public bool $valid;
    public string $password;
    public array $details;

    public function __construct(bool $valid, string $password, array $details) {
        $this->valid = $valid;
        $this->password = $password;
        $this->details = $details;
    }
}

class InputSanitizerData {
    public ?string $input;

    public function __construct(?string $input = null) {
        $this->input = $input;
    }
}

class SatinizerFormats {
    public bool $ascii;
    public bool $bitcoinAddress;
    public bool $cLikeIdentifier;
    public bool $coordinates;
    public bool $crediCard;
    public bool $date;
    public bool $discordUsername;
    public bool $doi;
    public bool $domain;
    public bool $e164Phone;
    public bool $email;
    public bool $emoji;
    public bool $hanUnification;
    public bool $hashtag;
    public bool $hyphenWordBreak;
    public bool $ipv6;
    public bool $ip;
    public bool $jiraTicket;
    public bool $macAddress;
    public bool $name;
    public bool $number;
    public bool $panFromGstin;
    public bool $password;
    public bool $port;
    public bool $tel;
    public bool $text;
    public bool $semver;

    public function __construct(
        bool $ascii,
        bool $bitcoinAddress,
        bool $cLikeIdentifier,
        bool $coordinates,
        bool $crediCard,
        bool $date,
        bool $discordUsername,
        bool $doi,
        bool $domain,
        bool $e164Phone,
        bool $email,
        bool $emoji,
        bool $hanUnification,
        bool $hashtag,
        bool $hyphenWordBreak,
        bool $ipv6,
        bool $ip,
        bool $jiraTicket,
        bool $macAddress,
        bool $name,
        bool $number,
        bool $panFromGstin,
        bool $password,
        bool $port,
        bool $tel,
        bool $text,
        bool $semver
    ) {
        $this->ascii = $ascii;
        $this->bitcoinAddress = $bitcoinAddress;
        $this->cLikeIdentifier = $cLikeIdentifier;
        $this->coordinates = $coordinates;
        $this->crediCard = $crediCard;
        $this->date = $date;
        $this->discordUsername = $discordUsername;
        $this->doi = $doi;
        $this->domain = $domain;
        $this->e164Phone = $e164Phone;
        $this->email = $email;
        $this->emoji = $emoji;
        $this->hanUnification = $hanUnification;
        $this->hashtag = $hashtag;
        $this->hyphenWordBreak = $hyphenWordBreak;
        $this->ipv6 = $ipv6;
        $this->ip = $ip;
        $this->jiraTicket = $jiraTicket;
        $this->macAddress = $macAddress;
        $this->name = $name;
        $this->number = $number;
        $this->panFromGstin = $panFromGstin;
        $this->password = $password;
        $this->port = $port;
        $this->tel = $tel;
        $this->text = $text;
        $this->semver = $semver;
    }
}

class SatinizerIncludes {
    public bool $spaces;
    public bool $hasSql;
    public bool $hasNoSql;
    public bool $letters;
    public bool $uppercase;
    public bool $lowercase;
    public bool $symbols;
    public bool $digits;

    public function __construct(
        bool $spaces,
        bool $hasSql,
        bool $hasNoSql,
        bool $letters,
        bool $uppercase,
        bool $lowercase,
        bool $symbols,
        bool $digits
    ) {
        $this->spaces = $spaces;
        $this->hasSql = $hasSql;
        $this->hasNoSql = $hasNoSql;
        $this->letters = $letters;
        $this->uppercase = $uppercase;
        $this->lowercase = $lowercase;
        $this->symbols = $symbols;
        $this->digits = $digits;
    }
}

class SatinizerResponse {
    public string $input;
    public SatinizerFormats $formats;
    public SatinizerIncludes $includes;

    public function __construct(string $input, SatinizerFormats $formats, SatinizerIncludes $includes) {
        $this->input = $input;
        $this->formats = $formats;
        $this->includes = $includes;
    }
}

class PrayerTimesData {
    public ?float $lat;
    public ?float $lon;

    public function __construct(?float $lat = null, ?float $lon = null) {
        $this->lat = $lat;
        $this->lon = $lon;
    }
}

class PrayerTimes {
    public string $coordinates;
    public string $date;
    public string $calculationParameters;
    public string $fajr;
    public string $sunrise;
    public string $dhuhr;
    public string $asr;
    public string $sunset;
    public string $maghrib;
    public string $isha;

    public function __construct(
        string $coordinates,
        string $date,
        string $calculationParameters,
        string $fajr,
        string $sunrise,
        string $dhuhr,
        string $asr,
        string $sunset,
        string $maghrib,
        string $isha
    ) {
        $this->coordinates = $coordinates;
        $this->date = $date;
        $this->calculationParameters = $calculationParameters;
        $this->fajr = $fajr;
        $this->sunrise = $sunrise;
        $this->dhuhr = $dhuhr;
        $this->asr = $asr;
        $this->sunset = $sunset;
        $this->maghrib = $maghrib;
        $this->isha = $isha;
    }
}

class PrayerTimesByTimezone {
    public string $timezone;
    public PrayerTimes $prayerTimes;

    public function __construct(string $timezone, PrayerTimes $prayerTimes) {
        $this->timezone = $timezone;
        $this->prayerTimes = $prayerTimes;
    }
}

class PrayerTimesResponse {
    public string $country;
    public array $prayerTimesByTimezone;

    public function __construct(string $country, array $prayerTimesByTimezone) {
        $this->country = $country;
        $this->prayerTimesByTimezone = $prayerTimesByTimezone;
    }
}

class DataVerifierURL {
    public ?bool $valid;
    public ?bool $fraud;
    public ?bool $freeSubdomain;
    public ?bool $customTLD;
    public ?string $url;
    public ?string $domain;
    public ?array $plugins;

    public function __construct(?bool $valid, ?bool $fraud, ?bool $freeSubdomain, ?bool $customTLD, ?string $url, ?string $domain, ?array $plugins) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->freeSubdomain = $freeSubdomain;
        $this->customTLD = $customTLD;
        $this->url = $url;
        $this->domain = $domain;
        $this->plugins = $plugins;
    }
}

class DataVerifierEmail {
    public ?bool $valid;
    public ?bool $fraud;
    public ?bool $proxiedEmail;
    public ?bool $freeSubdomain;
    public ?bool $corporate;
    public ?string $email;
    public ?string $realUser;
    public ?string $didYouMean;
    public ?string $noReply;
    public ?bool $customTLD;
    public ?string $domain;
    public ?bool $roleAccount;
    public ?array $plugins;

    public function __construct(
        ?bool $valid,
        ?bool $fraud,
        ?bool $proxiedEmail,
        ?bool $freeSubdomain,
        ?bool $corporate,
        ?string $email,
        ?string $realUser,
        ?string $didYouMean,
        ?string $noReply,
        ?bool $customTLD,
        ?string $domain,
        ?bool $roleAccount,
        ?array $plugins
    ) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->proxiedEmail = $proxiedEmail;
        $this->freeSubdomain = $freeSubdomain;
        $this->corporate = $corporate;
        $this->email = $email;
        $this->realUser = $realUser;
        $this->didYouMean = $didYouMean;
        $this->noReply = $noReply;
        $this->customTLD = $customTLD;
        $this->domain = $domain;
        $this->roleAccount = $roleAccount;
        $this->plugins = $plugins;
    }
}

class CarrierInfo {
    public string $carrierName;
    public float $accuracy;
    public string $carrierCountry;
    public string $carrierCountryCode;

    public function __construct(
        string $carrierName,
        float $accuracy,
        string $carrierCountry,
        string $carrierCountryCode
    ) {
        $this->carrierName = $carrierName;
        $this->accuracy = $accuracy;
        $this->carrierCountry = $carrierCountry;
        $this->carrierCountryCode = $carrierCountryCode;
    }
}

class DataVerifierPhone {
    public ?bool $valid;
    public ?bool $fraud;
    public ?string $phone;
    public ?string $prefix;
    public ?string $number;
    public ?string $lineType;
    public ?CarrierInfo $carrierInfo;
    public ?string $country;
    public ?string $countryCode;
    public ?array $plugins;

    public function __construct(
        ?bool $valid,
        ?bool $fraud,
        ?string $phone,
        ?string $prefix,
        ?string $number,
        ?string $lineType,
        ?string $country,
        ?string $countryCode,
        ?array $plugins,
        ?CarrierInfo $carrierInfo
    ) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->phone = $phone;
        $this->prefix = $prefix;
        $this->number = $number;
        $this->lineType = $lineType;
        $this->carrierInfo = $carrierInfo;
        $this->country = $country;
        $this->countryCode = $countryCode;
        $this->plugins = $plugins;
    }
}

class DataVerifierWallet {
    public ?bool $valid;
    public ?bool $fraud;
    public ?string $wallet;
    public ?string $type;
    public ?array $plugins;
    public function __construct(
        ?bool $valid,
        ?bool $fraud,
        ?string $wallet,
        ?string $type,
        ?array $plugins
    ) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->wallet = $wallet;
        $this->type = $type;
        $this->plugins = $plugins;
    }
}

class DataVerifierDomain {
    public ?bool $valid;
    public ?bool $fraud;
    public ?bool $freeSubdomain;
    public ?bool $customTLD;
    public ?string $domain;
    public ?array $plugins;

    public function __construct(?bool $valid, ?bool $fraud, ?bool $freeSubdomain, ?bool $customTLD, ?string $domain, ?array $plugins) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->freeSubdomain = $freeSubdomain;
        $this->customTLD = $customTLD;
        $this->domain = $domain;
        $this->plugins = $plugins;
    }
}

class DataVerifierUserAgent {
    public bool $valid;
    public ?string $type;
    public ?string $clientSlug;
    public ?string $clientName;
    public ?string $version;
    public ?string $userAgent;
    public ?bool $fraud;
    public ?bool $bot;
    public ?string $info;
    public ?string $os;
    public DataVerifierDevice $device;
    public ?VerifyPluginsResponse $plugins;

    public function __construct(
        bool $valid,
        ?string $type,
        ?string $clientSlug,
        ?string $clientName,
        ?string $version,
        ?string $userAgent,
        ?bool $fraud,
        ?bool $bot,
        ?string $info,
        ?string $os,
        DataVerifierDevice $device,
        ?VerifyPluginsResponse $plugins
    ) {
        $this->valid = $valid;
        $this->type = $type;
        $this->clientSlug = $clientSlug;
        $this->clientName = $clientName;
        $this->version = $version;
        $this->userAgent = $userAgent;
        $this->fraud = $fraud;
        $this->bot = $bot;
        $this->info = $info;
        $this->os = $os;
        $this->device = $device;
        $this->plugins = $plugins;
    }
}

class DataVerifierResponse {
    public DataVerifierURL $url;
    public DataVerifierEmail $email;
    public DataVerifierPhone $phone;
    public DataVerifierDomain $domain;
    public DataVerifierIp $ip;
    public DataVerifierWallet $wallet;
    public DataVerifierUserAgent $userAgent;

    public function __construct(DataVerifierURL $url, DataVerifierEmail $email, DataVerifierPhone $phone, DataVerifierDomain $domain, DataVerifierIp $ip, DataVerifierWallet $wallet, DataVerifierUserAgent $userAgent) {
        $this->url = $url;
        $this->email = $email;
        $this->phone = $phone;
        $this->domain = $domain;
        $this->ip = $ip;
        $this->wallet = $wallet;
        $this->userAgent = $userAgent;
    }
}

class Attachment {
    public string $filename;
    public ?string $path;
    public mixed $content;
    public ?string $cid;

    public function __construct(
        string $filename,
        ?string $path,
        mixed $content,
        ?string $cid
    ) {
        $this->filename = $filename;
        $this->path = $path;
        $this->content = $content;
        $this->cid = $cid;
    }
}

class EmailOptions {
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW = 'low';

    public ?string $priority;
    public ?bool $composeTailwindClasses;
    public ?bool $compileToCssSafe;
    public ?bool $onlyVerifiedEmails;

    public function __construct(
        ?string $priority = null,
        ?bool $composeTailwindClasses = false,
        ?bool $compileToCssSafe = false,
        ?bool $onlyVerifiedEmails = false
    ) {
        if ($priority !== null && !in_array($priority, [self::PRIORITY_HIGH, self::PRIORITY_NORMAL, self::PRIORITY_LOW])) $priority = null; // Assigning null if the priority value is invalid
        $this->priority = $priority;
        $this->composeTailwindClasses = $composeTailwindClasses;
        $this->compileToCssSafe = $compileToCssSafe;
        $this->onlyVerifiedEmails = $onlyVerifiedEmails;
    }
}

class SendEmail {
    public string $from;
    public string $to;
    public string $subject;
    public ?string $html;
    public mixed $react;
    public ?EmailOptions $options;
    public ?array $attachments;

    public function __construct(
        string $from,
        string $to,
        string $subject,
        mixed $react,
        ?string $html,
        ?EmailOptions $options,
        ?array $attachments
    ) {
        $this->from = $from;
        $this->to = $to;
        $this->subject = $subject;
        $this->html = $html;
        $this->react = $react;
        $this->options = $options;
        $this->attachments = $attachments;
    }
}

class SendEmailResponse {
    public bool $status;
    public ?string $error;

    public function __construct(bool $status, ?string $error) {
        $this->$status = $status;
        $this->$error = $error;
    }
}

class SRNG {
    public int $min;
    public int $max;
    public ?int $quantity;

    public function __construct(int $min, int $max, ?int $quantity = null) {
        $this->min = $min;
        $this->max = $max;
        $this->quantity = $quantity;
    }
}

class SRNGResponse {
    public array $values;
    public int|float $executionTime;

    public function __construct(array $values, int|float $executionTime) {
        $this->values = $values;
        $this->executionTime = $executionTime;
    }
}

class ExtractWithTextlyResponse {
    public array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }
}