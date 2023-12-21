<h3>Experience</h3>
<hr>

<div class="progress" style="height: 30px">
    <div class="progress-bar @if($vendor->experience < 0) bg-danger @endif" role="progressbar"
         style="width: {{$vendor->nextLevelProgress()}}%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
        Level {{$vendor->getLevel()}} ({{$vendor->nextLevelProgress()}}%)
    </div>
</div>
<div class="row mt-4">
    <div class="col">
        <p>Current level: <span>{{$vendor->getLevel()}}</span></p>
        <p>Current experience points: <span>{{$vendor->experience}}</span></p>
        <p>Experience required for next level: <span>{{max($vendor->nextLevelXp()-$vendor->experience,0)}}</span></p>
        @if($vendor->experience < 0)
            <div class="card mb-3">
                <div class="card-header">Negative experience</div>
                <div class="card-body">
                    <p>You have negative experience, all your offers will be labeled with this tag:</p>
                    <p class="text-danger border border-danger rounded p-1 mt-2 text-center"><span class="fas fa-exclamation-circle"></span> Negative experience, trade with caution !</p>
                </div>
                <div class="card-footer text-muted">
                    If you think this is an error, please contact administrator
                </div>
            </div>

        @endif
    </div>
</div>
