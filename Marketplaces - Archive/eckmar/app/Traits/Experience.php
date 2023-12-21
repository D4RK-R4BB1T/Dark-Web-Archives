<?php


namespace App\Traits;


use App\Marketplace\Utility\NumberFormat;

trait Experience
{
    public function getLevel(){
        $experience = $this->experience;
        if ($experience <= 0){
            return 1;
        }
        $levels = config('experience.levels');
        for($i = 1; $i <= sizeof($levels);$i++){
            $requiredXp = $levels[$i];
            if ($i+1 > sizeof($levels)){
                return $i;
            }
            if ($experience >= $requiredXp && $experience < $levels[$i+1])
            return $i;
        }
    }
    public function nextLevel(){
        $levels = config('experience.levels');
        $currentLevel = $this->getLevel();
        $nextLevel = $currentLevel+1;
        if ($nextLevel > sizeof($levels))
            return null;
        return $nextLevel;
    }
    public function nextLevelXp(){
        $levels = config('experience.levels');
        $nextLevel = $this->nextLevel();
        if ($nextLevel == null)
            return 0;
        return $levels[$nextLevel];
    }
    public function nextLevelProgress(){
        if ($this->nextLevelXp() == 0){
            return 100;
        }
        $progress = (1 - $this->experience / $this->nextLevelXp()) * 100;
        return round(100-$progress,0);
    }
    public function grantExperience($value){
        $this->experience += $value;
        $this->save();
    }
    public function takeExperience($value){
        $this->experience -= $value;
        $this->save();
    }

    public function getShortXP(){

        return NumberFormat::short($this->experience);
    }
}