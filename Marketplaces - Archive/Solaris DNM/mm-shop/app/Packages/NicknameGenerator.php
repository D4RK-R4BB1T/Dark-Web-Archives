<?php
/**
 * File: NicknameGenerator.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages;


class NicknameGenerator
{
    protected static $adjs = ["autumn",  "hidden",  "bitter",  "misty",  "silent",  "empty",
        "dry",  "dark", "summer",  "icy",  "delicate",  "quiet",  "white",  "cool",
        "spring",  "winter", "patient",  "twilight",  "dawn",  "crimson",  "wispy",
        "weathered",  "blue", "billowing",  "broken",  "cold",  "damp",  "falling",
        "frosty",  "green", "long",  "late",  "lingering",  "bold",  "little",
        "morning",  "muddy",  "old", "red",  "rough",  "still",  "small",
        "sparkling",  "throbbing",  "shy", "wandering",  "withered",  "wild",
        "black",  "young",  "holy",  "solitary", "fragrant",  "aged",  "snowy",
        "proud",  "floral",  "restless",  "divine", "polished",  "ancient",  "purple",
        "lively",  "nameless"];

    protected static $nouns = ["waterfall", "river", "breeze", "moon", "rain", "wind", "sea", "morning",
        "snow", "lake", "sunset", "pine", "shadow", "leaf", "dawn", "glitter",
        "forest", "hill", "cloud", "meadow", "sun", "glade", "bird", "brook",
        "butterfly", "bush", "dew", "dust", "field", "fire", "flower", "firefly",
        "feather", "grass", "haze", "mountain", "night", "pond", "darkness",
        "snowflake", "silence", "sound", "sky", "shape", "surf", "thunder",
        "violet", "water", "wildflower", "wave", "water", "resonance", "sun",
        "wood", "dream", "cherry", "tree", "fog", "frost", "voice", "paper",
        "frog", "smoke", "star"];

    public static function generateNickname($seed) {
        mt_srand($seed);
        $randomAdjective = self::$adjs[mt_rand(0, count(self::$adjs) - 1)];
        $randomNoun = self::$nouns[mt_rand(0, count(self::$nouns) - 1)];
        $randomNumber = mt_rand(1, 99);
        return $randomAdjective . $randomNoun . $randomNumber;
    }
}