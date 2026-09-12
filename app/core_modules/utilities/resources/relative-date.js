/* Local calendar-day labels for semantic time elements; wording comes from the language system. */
(function () {
    'use strict';
    if(window.ChisimbaRelativeDatesReady)return;
    window.ChisimbaRelativeDatesReady=true;
    function calendarDay(date){return Date.UTC(date.getFullYear(),date.getMonth(),date.getDate())/86400000;}
    function update(){
        var now=new Date(),language=document.documentElement.lang||'en-GB';
        var locale=/^en(?:-|$)/i.test(language)?'en-GB':language;
        document.querySelectorAll('time[data-relative-date]').forEach(function(element){
            var date=new Date(element.dateTime);if(!Number.isFinite(date.getTime()))return;
            var days=calendarDay(now)-calendarDay(date);
            var full=new Intl.DateTimeFormat(locale,{year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit',timeZoneName:'short'}).format(date);
            element.title=full;element.setAttribute('aria-label',full);
            element.textContent=days===0?element.dataset.today:days===1?element.dataset.yesterday:days>1?element.dataset.daysAgo.replace('[-days-]',String(days)):date.toLocaleDateString(locale);
        });
    }
    update();
    document.addEventListener('visibilitychange',function(){if(!document.hidden)update();});
    setInterval(update,60000);
}());
