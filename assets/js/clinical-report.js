/* Clinical report: automatically query MySQL for only tag-3716 users in bounded batches. No jQuery. */
(function () {
  'use strict';
  var run = document.getElementById('btn-clinical-run');
  if (!run || typeof btnClinicalReport === 'undefined') return;
  var preset = document.getElementById('btn-clinical-preset');
  var from = document.getElementById('btn-clinical-from');
  var to = document.getElementById('btn-clinical-to');
  var apply = document.getElementById('btn-clinical-apply');
  var input = document.getElementById('btn-clinical-emails');
  var stop = document.getElementById('btn-clinical-stop');
  var resume = document.getElementById('btn-clinical-resume');
  var download = document.getElementById('btn-clinical-download');
  var status = document.getElementById('btn-clinical-progress');
  var output = document.getElementById('btn-clinical-results');

  var running = false;
  var complete = false;
  var rows = [];
  var cursor = 0;
  var upper = 0;
  var scanned = 0;
  var limit = 25;
  var failuresAtSmallest = 0;
  var emails = [];
  var mode = 'all';
  var startDate = '';
  var endDate = '';
  var activeRequest = null;
  var generation = 0;
  var timer = null;
  var seenEmails = new Set();

  function message(text) { status.textContent = text; }
  function createCell(value, tr) {
    var td = document.createElement('td');
    td.textContent = String(value == null ? '' : value);
    tr.appendChild(td);
  }
  function csvEscape(value) {
    var v = String(value == null ? '' : value);
    if (/^\s*[=+@\-]/.test(v)) v = "'" + v;
    return '"' + v.replace(/"/g, '""') + '"';
  }
  function dateShift(dateString, days) {
    var date = new Date(dateString + 'T12:00:00Z');
    date.setUTCDate(date.getUTCDate() + days);
    return date.toISOString().slice(0, 10);
  }
  function presetDates() {
    if (preset.value === 'all') {
      from.value = '';
      to.value = '';
      from.disabled = true;
      to.disabled = true;
    } else if (preset.value === '7' || preset.value === '30') {
      to.value = btnClinicalReport.today;
      from.value = dateShift(btnClinicalReport.today, -Number(preset.value) + 1);
      from.disabled = true;
      to.disabled = true;
    } else {
      from.disabled = false;
      to.disabled = false;
      if (!from.value) from.value = dateShift(btnClinicalReport.today, -6);
      if (!to.value) to.value = btnClinicalReport.today;
    }
  }
  function setButtons() {
    run.disabled = running;
    stop.disabled = !running;
    if (resume) resume.disabled = running || complete;
    // Partial reports may still be exported, but are clearly marked as partial.
    download.disabled = rows.length === 0;
    download.textContent = complete ? 'Download CSV' : 'Download partial CSV';
  }
  function cancelActive() {
    generation++;
    running = false;
    if (activeRequest) activeRequest.abort();
    activeRequest = null;
    if (timer) window.clearTimeout(timer);
    timer = null;
  }
  function requestParams() {
    var params = new URLSearchParams();
    params.append('action', 'btn_podcasts_clinical_batch');
    params.append('nonce', btnClinicalReport.nonce);
    params.append('mode', mode);
    params.append('cursor', String(cursor));
    params.append('upper', String(upper));
    params.append('limit', String(limit));
    params.append('start', startDate);
    params.append('end', endDate);
    if (mode === 'selected') {
      emails.forEach(function (email) { params.append('emails[]', email); });
    }
    return params;
  }
  function appendRows(newRows) {
    var fragment = document.createDocumentFragment();
    newRows.forEach(function (record) {
      var identifier = String(record.email || '').toLowerCase();
      // A WordPress user can have more than one memb_Groups metadata row.
      if (seenEmails.has(identifier)) return;
      seenEmails.add(identifier);
      rows.push(record);
      var tr = document.createElement('tr');
      [record.email, record.lastLogin, record.firstListen, record.streak, record.rangeTotal, record.total]
        .forEach(function (v) { createCell(v, tr); });
      fragment.appendChild(tr);
    });
    output.appendChild(fragment);
  }
  function finish() {
    running = false;
    complete = true;
    setButtons();
    message('Complete — ' + rows.length + ' participant(s) with exact tag 3716 (matched in MySQL). ' + (mode === 'all' ? 'Matching metadata rows: ' + scanned + '.' : 'Selected emails checked: ' + scanned + '.'));
  }
  function stopWithError(messageText) {
    running = false;
    complete = false;
    setButtons();
    if (resume) resume.style.display = '';
    message('Report incomplete: ' + messageText + ' — ' + rows.length + ' participant(s) loaded. Resume or refresh; any CSV downloaded now is partial.');
  }
  function nextBatch(token) {
    if (!running || generation !== token) return;
    var controller = new AbortController();
    activeRequest = controller;
    var timeout = window.setTimeout(function () { controller.abort(); }, 25000);
    fetch(btnClinicalReport.ajaxUrl, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: requestParams().toString(), signal: controller.signal
    }).then(function (response) {
      return response.text().then(function (body) {
        var result;
        try { result = JSON.parse(body); } catch (ignore) { result = null; }
        if (!response.ok || !result || !result.success || !result.data) {
          var detail = result && result.data && result.data.message;
          var error = new Error(detail || ('Request failed: HTTP ' + response.status));
          error.status = response.status;
          throw error;
        }
        return result.data;
      });
    }).then(function (result) {
      if (!running || generation !== token) return;
      activeRequest = null;
      failuresAtSmallest = 0;
      appendRows(result.rows || []);
      var oldCursor = cursor;
      cursor = Number(result.nextCursor) || 0;
      upper = Number(result.upper) || 0;
      scanned += Number(result.checked) || 0;
      if (result.done) {
        finish();
      } else if (cursor <= oldCursor) {
        stopWithError('Report cursor did not advance; stopping to avoid an infinite loop');
      } else {
        message('Loading automatically… MySQL matched ' + scanned + ' tag-3716 metadata record(s); found ' + rows.length + ' participants.');
        timer = window.setTimeout(function () { nextBatch(token); }, 500);
      }
    }).catch(function (err) {
      if (!running || generation !== token) return;
      activeRequest = null;
      var statusCode = Number(err.status) || 0;
      var retryable = statusCode === 502 || statusCode === 504 || statusCode === 429 ||
        (statusCode === 503 && !/Memberium.*unavailable/i.test(err.message)) || err.name === 'AbortError' || statusCode === 0;
      if (retryable) {
        if (limit > 1) {
          limit = limit > 25 ? 25 : (limit > 5 ? 5 : 1);
          message('Server is slow (' + err.message + '). Retrying this exact batch with ' + limit + ' metadata row(s) per request…');
          timer = window.setTimeout(function () { nextBatch(token); }, 900);
          return;
        }
        if (failuresAtSmallest++ < 2) {
          message('Server is still slow for one account (' + err.message + '). Retrying safely…');
          timer = window.setTimeout(function () { nextBatch(token); }, failuresAtSmallest * 1500);
          return;
        }
      }
      stopWithError(err.message);
    }).finally(function () { window.clearTimeout(timeout); });
  }
  function parsedEmails() {
    return Array.from(new Set(input.value.split(/[\s,;]+/).map(function (v) {
      return v.trim();
    }).filter(Boolean))).slice(0, 500);
  }
  function startReport(forceFull) {
    cancelActive();
    startDate = from.value;
    endDate = to.value;
    if ((startDate && !endDate) || (!startDate && endDate) || (startDate && endDate && startDate > endDate)) {
      stopWithError('Please select a valid date range');
      return;
    }
    var filteredEmails = parsedEmails();
    if (filteredEmails.length) {
      mode = 'selected';
      emails = filteredEmails;
    } else {
      // Re-query MySQL on date changes so tag additions/removals are reflected.
      // Do not retrieve a list of untagged accounts or reuse stale client data.
      mode = 'all';
      emails = [];
    }
    rows = [];
    seenEmails = new Set();
    output.textContent = '';
    cursor = 0;
    upper = 0;
    scanned = 0;
    limit = 25;
    failuresAtSmallest = 0;
    complete = false;
    if (resume) resume.style.display = 'none';
    running = true;
    setButtons();
    message('Querying MySQL for users with memb_Groups tag 3716…');
    nextBatch(generation);
  }

  run.addEventListener('click', function () { input.value = ''; startReport(true); });
  apply.addEventListener('click', function () { startReport(false); });
  preset.addEventListener('change', function () { presetDates(); startReport(false); });
  [from, to].forEach(function (element) {
    element.addEventListener('change', function () { if (preset.value === 'custom') startReport(false); });
  });
  stop.addEventListener('click', function () {
    cancelActive();
    complete = false;
    if (resume) resume.style.display = '';
    setButtons();
    message('Paused — ' + rows.length + ' participants loaded. Click Resume to continue.');
  });
  if (resume) resume.addEventListener('click', function () {
    if (running || complete) return;
    running = true;
    failuresAtSmallest = 0;
    resume.style.display = 'none';
    setButtons();
    nextBatch(generation);
  });
  download.addEventListener('click', function () {
    if (!rows.length) return;
    var headers = ['Email', 'Last login (latest, site timezone)', 'First qualified listen (all time, site timezone)', 'Consecutive days as of end date', 'Listens in date range', 'Total listens (all time)'];
    var lines = [headers];
    rows.forEach(function (record) { lines.push([record.email, record.lastLogin, record.firstListen, record.streak, record.rangeTotal, record.total]); });
    var csv = '\uFEFF' + lines.map(function (columns) { return columns.map(csvEscape).join(','); }).join('\r\n');
    var url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
    var anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = 'nhs-clinical-3716-' + (startDate || 'all') + '-to-' + (endDate || 'all') + (complete ? '' : '-PARTIAL') + '.csv';
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
  });
  presetDates();
  startReport(true);
}());
