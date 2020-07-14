$(() => {
    var timeSource = '/time.php';
    var timeOffset = 0;
    var timeSynced = false;
    var timeUpdates = 0;

    $('.countdown-timer').each(function (e) {
        var $wrapper = $(this);
        var $remaining = $wrapper.find('.countdown-time-remaining');
        var targetTime = parseInt($wrapper.attr('data-time'));
        var refresh = $wrapper.attr('data-refresh') == 'true';
        var refreshing = false;
        // function to set up time
        var updateTimer = function () {
            var remaining = remainingTime(targetTime);
            $remaining.text(fuzzyTimeText(remaining));
            if (refresh && timeSynced && remaining <= 0 && !refreshing) {
                window.location.reload(true);
                refreshing = true;
            }
        };
        updateTimer();
        // set up timer
        setInterval(updateTimer, 1000);
    });

    function fuzzyTimeText(seconds) {
        var units = [
            ['week', 86400 * 7],
            ['day', 86400],
            ['hour', 60 * 60],
            ['minute', 60],
            ['second', 1]
        ];
        var out = [];
        for (let i = 0; i < units.length; i++) {
            const unit = units[i];
            if (seconds >= unit[1]) {
                count = Math.floor(seconds / unit[1]);
                seconds = seconds - count * unit[1];
                if (count == 1) {
                    out.push("1 " + unit[0]);
                } else {
                    out.push(count + " " + unit[0] + "s");
                }
            }
        }
        return out.join(' ');
    }

    function remainingTime(targetTime) {
        return targetTime - currentTime();
    }

    function currentTime() {
        var now = new Date();
        now = Math.round(now.getTime() / 1000);
        return now + timeCorrection();
    }

    function timeCorrection() {
        return timeOffset;
    }

    function syncTime() {
        var start = new Date();
        $.get(
            timeSource,
            function (data) {
                var end = new Date();
                var diff = (end.getTime() - start.getTime()) / 1000;
                var serverTime = parseInt(data) + (diff / 4);
                var localTime = currentTime();
                var timeOffsetCorrection = serverTime - localTime;
                timeOffset += timeOffsetCorrection;
                // toggle visibility of everything
                if (!timeSynced) {
                    // toggle visibility
                    timeSynced = true;
                    $('.countdown-js').show();
                    $('.countdown-nojs').hide();
                }
                // adapt time until next sync by the amount of correction needed
                timeUpdates++;
                if (timeUpdates < 5) {
                    var runAgainIn = Math.ceil(Math.abs(10 / timeOffsetCorrection));
                    setTimeout(syncTime, runAgainIn * 1000);
                }
            }
        );
    }
    if ($('.countdown-timer').length > 0) {
        syncTime();
    }
});