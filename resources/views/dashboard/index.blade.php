@extends('layouts.main')

@section('title')
  Dashboard
@endsection

@section('nav1')
  class="active"
@endsection

@section('header')
<link href="/res/css/training.css" rel="stylesheet" type="text/css">
<link href="/res/css/training-lections.css" rel="stylesheet" type="text/css">
@endsection

@section('content')

<div class="container" style="margin-top: 30px; min-height: 100vh;">

  <div class="lection-nav">
    <a href="/dashboard?view=lections" style="text-decoration:none">
      <div id="item-lections" class="nav-item unselectable @echoIf($view == 'lections', 'item-active')">
        @lang('dashboard.lection')
      </div>
    </a>
    <a href="{{ url('/dashboard?view=exercises') }}" style="text-decoration:none">
      <div id="item-exercises" class="nav-item unselectable @echoIf($view == 'exercises', 'item-active')">
        @lang('dashboard.exercise')
      </div>
    </a>
  </div>
  <div></div> {{-- to fix css issue with float… --}}

  <div class="lection-panel">

    @if($view == 'lections')
    <div id="container-lections">{{-- container for lections --}}

      @foreach($lections as $lection)
        <div class="lection-item">
          <div class="lection-num">@lang('dashboard.lection') {{ $lection->external_id }}</div>
          <div class="lection-title">{{ $lection->title }}</div>
          <div class="lection-footer">
            <a href="{{ url("/lection/$lection->external_id") }}">
              <span class="lection-link">@lang('dashboard.start')</span>
            </a>
          </div>
        </div>
      @endforeach

    </div>

    @elseif($view == 'exercises')
    <div id="container-exercises">

      <p style="padding-left: 32px; margin-top: -18px;">
        @lang('dashboard.exerciseInfo')
      </p>

      @foreach ($exercises as $exercise)
        <div class="lection-item">
          <div class="lection-options">
            <a class="dropdown-toggle glyphicon glyphicon-option-vertical lection-options-icon" data-toggle="dropdown" href="" style="text-decoration:none">
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
              <li>
                <a href="{{ url("/exercise/$exercise->external_id/edit") }}">
                  @lang('exercise.edit.action')
                </a>
              </li>
              <li class="divider"></li>
              <li>
                <a href="#" onclick="deleteExercise(event, '{{ $exercise->external_id }}');">
                @lang('exercise.delete.action')
              </a>
              </li>
            </ul>
          </div>
          <div class="lection-title" style="margin-top:12px">{{ $exercise->title }}</div>
          <div class="lection-footer">
            <a href="{{ url("/exercise/$exercise->external_id") }}">
              <span class="lection-link">@lang('dashboard.start')</span>
            </a>
          </div>
        </div>
      @endforeach

      <a href="{{ url('/exercise') }}" class="lection-item-add"><span class="glyphicon glyphicon-plus"></span></a>

    </div>
    @endif

  </div>

  <div class="extra-panel">

    <div>
      <a href="{{ url('/training') }}" class="btn btn-default btn-main btn-training" style="font-size: 17px;"><span><span class="glyphicon glyphicon-education"></span> @lang('dashboard.training')</span></a>
    </div>

    <div class="circle-container" data-toggle="tooltip" title="heutige XP">
      <div style="position: relative;">
        <canvas id="xp-graph" height="250" width="250"></canvas>
        <div style="position: absolute; top: 42.5%; text-align: center; width: 100%; font-size: 17px; font-family: Montserrat; cursor: default;" class="unselectable">
          {{ $xp }} / {{ $xp_goal }} XP
        </div>
      </div>
    </div>

    <div data-toggle="tooltip" title="XP der letzten Woche">
      <canvas id="graph" width="160" height="100"></canvas>
    </div>

  </div>

</div>

<script>
function deleteExercise(e, id) {
  e.preventDefault();
  var url = "{{ url('/exercise') }}" + "/";
  document.getElementById("delete-exercise-form").action = url + id + "/delete";
  $("#modal_delete").modal();
}
</script>
<div id="modal_delete" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">@lang('exercise.delete.title')</h4>
      </div>
      <div class="modal-body">
        <p>@lang('exercise.delete.content')</p>
      </div>
      <div class="modal-footer">
        <form id="delete-exercise-form" method="POST" action="">
          {{ csrf_field() }}
          <button type="submit" class="btn btn-danger">@lang('exercise.delete.action')</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">@lang('info.back')</button>
        </form>
      </div>
    </div>

  </div>
</div>

{{--<div id="modal_publish" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Lektion veröffentlichen?</h4>
      </div>
      <div class="modal-body">
        <p>Wenn du die Lektion veröffentlichen willst, musst du bestimmte Regeln einhalten.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="commitPublish();">Veröffentlichen</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
      </div>
    </div>

  </div>
</div>--}}

@endsection

@section('footer')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js"></script>
<script src="/res/js/stats.min.js"></script>
<script>
var xp = {{ $xp }};
var goal = {{ $xp_goal }};
view = "xp";
lang.xp = "@lang('stats.xp.title')";
no_selectpicker = true;

$(document).ready(function() {

  // show last 7 days
  updateChart({
    from:     moment().subtract(7, 'days').format(DATE_FORMAT),
    to:       moment().format(DATE_FORMAT),
    days:     7,
    selector: "last"
  });

  // chart
  var ctx = document.getElementById("xp-graph");
  var dif = Math.max(0, goal - xp);

  Chart.defaults.global.legend.display = false;
  Chart.defaults.global.defaultFontFamily = "'Montserrat', 'Arial', 'sans-serif'";

  var data = {
      labels: [
          "@lang('training.results.currentXP')",
          "@lang('training.results.missingXP')"
      ],
      datasets: [
          {
              data: [xp, dif],
              backgroundColor: [
                  "#ff5722",
                  "#ffab91"
              ],
              borderColor: [
                  "#ff5722",
                  "#ffab91"
              ],
              hoverBackgroundColor: [
                  "#ff5722",
                  "#ffab91"
              ]
          }]
  };

  var chart = new Chart(ctx, {
    type: 'doughnut',
    data: data,
    animation:{
          animateScale: true
    },
    options: {
      cutoutPercentage: 70
    }
  });
});
</script>
@endsection
