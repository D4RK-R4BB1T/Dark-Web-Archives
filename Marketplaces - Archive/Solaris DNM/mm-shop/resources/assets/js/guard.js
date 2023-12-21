
function getJSON(url, callback, error) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", url, true);
    xhr.onload = function (e) {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                var res = xhr.responseText;
                callback(JSON.parse(res));
            } else {
                error(xhr.statusText);
            }
        }
    };
    xhr.onerror = function (e) {
        error(xhr.statusText);
    };
    xhr.send(null);
}


var js_salt = atob("d2h5bm90Pw==");
var challenge_url = "/guard/challenge_question";
var challenge_answer_url = "/guard/challenge_answer";

function reportError(e) {
    alert("К сожалению, при верификации браузера произошла ошибка. Обновите страницу и попробуйте еще раз.");
}

setTimeout(function() {
    getJSON(challenge_url, function(res) {
        if (typeof(res.t) == "undefined" || typeof(res.s) == "undefined" || typeof(res.a) == "undefined") {
            return reportError(null);
        }
        var solved = false;
        for (var i = 10000; i < 99999; i++) {
            var answer = window['sha1'](res.t + res.s + i + "_" + js_salt);
            if (answer === res.a) {
                solved = true;
                break;
            }
        }
        if (!solved) {
            alert(i);
            return reportError(null);
        }

        getJSON(challenge_answer_url + "?t=" + res.t + "&a=" + i, function(res2) {
            if (typeof(res.s) !== "undefined") {
                return location.replace("/");
            }

            return reportError(null);
        }, reportError);
    }, reportError);
}, 1000);