<?php

declare(strict_types=1);

namespace Dymo\Models;

class UrlEncryptResponse {
    public string $original;
    public string $code;
    public string $encrypt;

    public function __construct(string $original, string $code, string $encrypt)
    {
        $this->original = $original;
        $this->code = $code;
        $this->encrypt = $encrypt;
    }
}

class IsValidPwdDetails {
    public string $validation;
    public string $message;

    public function __construct(string $validation, string $message)
    {
        $this->validation = $validation;
        $this->message = $message;
    }
}

class IsValidPwdResponse {
    public bool $valid;
    public string $password;
    public array $details;

    public function __construct(bool $valid, string $password, array $details)
    {
        $this->valid = $valid;
        $this->password = $password;
        $this->details = $details;
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

    public function __construct(string $input, SatinizerFormats $formats, SatinizerIncludes $includes)
    {
        $this->input = $input;
        $this->formats = $formats;
        $this->includes = $includes;
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

    public function __construct(string $timezone, PrayerTimes $prayerTimes)
    {
        $this->timezone = $timezone;
        $this->prayerTimes = $prayerTimes;
    }
}

class PrayerTimesResponse {
    public string $country;
    public array $prayerTimesByTimezone; // Array de PrayerTimesByTimezone

    public function __construct(string $country, array $prayerTimesByTimezone)
    {
        $this->country = $country;
        $this->prayerTimesByTimezone = $prayerTimesByTimezone;
    }
}

class DataVerifierEmail {
    public ?bool $valid;
    public ?bool $fraud;
    public ?bool $freeSubdomain;
    public ?bool $corporate;
    public ?string $email;
    public ?string $realUser;
    public ?bool $customTLD;
    public ?string $domain;
    public ?bool $roleAccount;
    public ?array $plugins; // Array de key=>value

    public function __construct(
        ?bool $valid,
        ?bool $fraud,
        ?bool $freeSubdomain,
        ?bool $corporate,
        ?string $email,
        ?string $realUser,
        ?bool $customTLD,
        ?string $domain,
        ?bool $roleAccount,
        ?array $plugins
    ) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->freeSubdomain = $freeSubdomain;
        $this->corporate = $corporate;
        $this->email = $email;
        $this->realUser = $realUser;
        $this->customTLD = $customTLD;
        $this->domain = $domain;
        $this->roleAccount = $roleAccount;
        $this->plugins = $plugins;
    }
}

class DataVerifierPhone {
    public ?bool $valid;
    public ?bool $fraud;
    public ?string $phone;
    public ?string $prefix;
    public ?string $number;
    public ?string $country;
    public ?array $plugins; // Array de key=>value

    public function __construct(
        ?bool $valid,
        ?bool $fraud,
        ?string $phone,
        ?string $prefix,
        ?string $number,
        ?string $country,
        ?array $plugins
    ) {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->phone = $phone;
        $this->prefix = $prefix;
        $this->number = $number;
        $this->country = $country;
        $this->plugins = $plugins;
    }
}

class DataVerifierDomain {
    public ?bool $valid;
    public ?bool $fraud;
    public ?string $domain;
    public ?array $plugins; // Array de key=>value

    public function __construct(?bool $valid, ?bool $fraud, ?string $domain, ?array $plugins)
    {
        $this->valid = $valid;
        $this->fraud = $fraud;
        $this->domain = $domain;
        $this->plugins = $plugins;
    }
}

class DataVerifierResponse {
    public DataVerifierEmail $email;
    public DataVerifierPhone $phone;
    public DataVerifierDomain $domain;

    public function __construct(DataVerifierEmail $email, DataVerifierPhone $phone, DataVerifierDomain $domain)
    {
        $this->email = $email;
        $this->phone = $phone;
        $this->domain = $domain;
    }
}


class SendEmailResponse {
    public bool $status;
    public ?string $error;

    public function __construct(bool $status, ?string $error){
        $this->$status = $status;
        $this->$error =$error;
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