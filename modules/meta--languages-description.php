<?php
	$standard_name = get_field('standard_name');
	$alternate_names = get_field('alternate_names');
	$iso_code = get_field('iso_code');
	$glottocode = get_field('glottocode');
	$nations_of_origin = get_field('nations_of_origin');
	$writing_systems = get_field('writing_systems');
	$linguistic_genealogy = get_field('linguistic_genealogy');

// Example variables (you can change these values to test different scenarios)
$A = $standard_name;   // This should always be present
$B = $nations_of_origin;    // This can be optional
$C = $linguistic_genealogy;   // This can be optional
$D = $writing_systems;     // This can be optional

// Initialize an empty sentence
$sentence = "";

// Handle the case when B is present and construct the main sentence accordingly
if (!empty($B)) {
    $sentence = "$A is a language of $B";
    if (!empty($C)) {
        if (strtolower($C) === "language isolate") {
            $sentence .= ", and is a language isolate";
        } else {
            $sentence .= ", and is a member of the $C macro family";
        }
    }
} elseif (!empty($C)) {
    if (strtolower($C) === "language isolate") {
        $sentence = "$A is a language isolate";
    } else {
        $sentence = "$A is a member of the $C macro family";
    }
}

// Handle the addition of D (the script part)
if (!empty($D)) {
    // Normalize the value of D to lowercase for comparison
    if (strtolower($D) === "unwritten") {
        $sentence .= ". It does not have an official script.";
    } else {
        // If both B and C are present, D is added as a new sentence.
        if (!empty($B) || !empty($C)) {
            $sentence .= ". It is usually written with $D script.";
        } else {
            $sentence .= " It is usually written with $D script.";
        }
    }
} else {
    $sentence .= ".";
}

// Additional logic for when combinations of 3 are present
if (empty($B) && empty($C) && !empty($D)) {
    if (strtolower($D) === "unwritten") {
        $sentence = "$A does not have an official script.";
    } else {
        $sentence = "$A is usually written with $D script.";
    }
} elseif (empty($B) && !empty($C) && !empty($D)) {
    if (strtolower($C) === "language isolate") {
        $sentence = "$A is a language isolate.";
    } elseif (strtolower($D) === "unwritten") {
        $sentence = "$A is a member of the $C macro family. It does not have an official script.";
    } else {
        $sentence = "$A is a member of the $C macro family. It is usually written with $D script.";
    }
} elseif (!empty($B) && empty($C) && !empty($D)) {
    if (strtolower($D) === "unwritten") {
        $sentence = "$A is a language of $B. It does not have an official script.";
    } else {
        $sentence = "$A is a language of $B. It is usually written with $D script.";
    }
} elseif (!empty($B) && !empty($C) && empty($D)) {
    if (strtolower($C) === "language isolate") {
        $sentence = "$A is a language of $B, and is a language isolate.";
    } else {
        $sentence = "$A is a language of $B, and is a member of the $C macro family.";
    }
}

echo '<script>console.log(' . json_encode($B) . ')</script>';
// Output the constructed sentence
?>

<p><?php echo $sentence ?></p>