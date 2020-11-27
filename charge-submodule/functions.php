<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-ability_elemental-buster_before' => function($objects){
        extract($objects);
        $options->buster_charge_required = false;
        return true;
    },
    'rpg-ability_elemental-buster_onload_before' => function($objects){
        extract($objects);
        $options->buster_charge_required = false;
        return true;
    }
);
?>
