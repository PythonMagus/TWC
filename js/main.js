function requestEmailText(action)
{
    var form = document.querySelector('form.EmailText');
    form.style.display = '';
    document.querySelector('form.EmailText input[type="hidden"]').value = action;
    document.querySelector('form.EmailText button.Hide').onclick = function() {form.style.display = 'none'; return false;}
    document.querySelector('form.EmailText h2').innerHTML = action == 'suspend' ? 'You are about to suspend the game' :
        action == 'unsuspend' ? 'You are about to resume the game' :
        action == 'cancel' ? 'You are about to cancel the game' :
        action == 'start' ? 'You are about to start the game' :
        ('You are about to ' + action + ' the game');
    var text = document.querySelector('form.EmailText textarea');
    text.placeholder = 'Text to accompany';
}
function loadBattleEditor()
{
    function getPlayer(id) {
        for (var i = 0; i < users.length; i++)
            if (users[i].id == id)
                return users[i];
        return {};
    }
    function setUp() {
        var playerList = document.querySelector('ul.Players');
        var addPlayerSelect = document.querySelector('#addPlayer');
        var removePlayerSelect = document.querySelector('#removePlayer');
        var nameField = document.querySelector('input[name="name"]');
        var playerField = document.querySelector('input[name="players"]');
        var saveButton = document.querySelector('input[type="submit"]');
        loadDateEditor(document.querySelector('input[name="started"]'));
        nameField.onkeyup = function()
        {
            saveButton.disabled = !players.length || !nameField.value;
        }
        function addPlayer(id) {
            var node = document.querySelector('#addPlayer option[value="' + id + '"]');
            addPlayerSelect.removeChild(node);
            removePlayerSelect.appendChild(node);
            players.push(id);
            playerField.value = JSON.stringify(players);
            var player = getPlayer(id);
            node = document.createElement('li');
            node.setAttribute("userId", id);
            node.innerHTML = '<b>' + player.alias + '</b> <a href="mailto:' + player.email + '">' + player.email + '</a>';
            playerList.appendChild(node);
            addPlayerSelect.options.selectedIndex = 0;
            removePlayerSelect.options.selectedIndex = 0;
            saveButton.disabled = readOnly || !nameField.value;
        }
        function removePlayer(id) {
            var node = document.querySelector('#removePlayer option[value="' + id + '"]');
            removePlayerSelect.removeChild(node);
            addPlayerSelect.appendChild(node);
            var idx = players.indexOf(id);
            if (idx != -1) players.splice(idx, 1);
            playerField.value = JSON.stringify(players);
            node = document.querySelector('ul.Players li[userid="' + id + '"]');
            playerList.removeChild(node);
            addPlayerSelect.options.selectedIndex = 0;
            removePlayerSelect.options.selectedIndex = 0;
            saveButton.disabled = !players.length || !nameField.value;
        }
        addPlayerSelect.onchange = function(evt) {addPlayer(parseInt(evt.target.value))};
        removePlayerSelect.onchange = function(evt) {removePlayer(parseInt(evt.target.value))};
        for (var i = 0; i < users.length; i++) {
            var user = users[i];
            var option = document.createElement('option');
            option.setAttribute("value", user.id);
            option.setAttribute("title", user.rankName);
            option.innerHTML = user.alias;
            addPlayerSelect.appendChild(option);
        }
        document.querySelector('input.Filter').onkeyup = function(evt) {
            var substr = evt.target.value;
            var test = substr ? new RegExp('\\b' + substr.replace(/[a-z0-9_ ]/gi, function(s) {return '\\' + s}),'i') : {test: function(){return true}};
            for (var i = 1; i < addPlayerSelect.options.length; i++){
                var option = addPlayerSelect.options[i];
                option.style.display= test.test(option.text) ? '' : 'none';
            }
        }
        var p = players;
        players = [];
        if (p) for (var i = 0; i < p.length; i++) addPlayer(p[i]);
    }
    window.addEventListener('load', setUp);
}

function loadTournament() {
    function getPlayer(id) {
        for (var i = 0; i < users.length; i++)
            if (users[i].id == id)
                return users[i];
        return {};
    }
    function setUp() {
        window.playerLU = {}
        for (var i = 0; i < users.length; i++) window.playerLU[users[i].id] = users[i].alias;
        var playerList = document.querySelector('ul.Players');
        var addPlayerSelect = document.querySelector('#addPlayer');
        var removePlayerSelect = document.querySelector('#removePlayer');
        var stateSelect = document.querySelector('select[name="state"]');
        var typeSelect = document.querySelector('select[name="type"]');
        var nameField = document.querySelector('input[name="name"]');
        var playerField = document.querySelector('input[name="players"]');
        var saveButton = document.querySelector('input[type="submit"]');
        var drawPanel = document.querySelector('.DrawPanel');
        var drawAccepted = document.querySelector('#drawAccepted');
        loadDateEditor(document.querySelector('input[name="started"]'));
        function calculateSaveValidity() {
             saveButton.disabled = state == 'Complete' || !players.length || !nameField.value || 
                (stateSelect.selectedIndex == 1 && (players.length < 2 || typeSelect.selectedIndex == 0 && !drawAccepted.checked));
        }
        nameField.onkeyup = drawAccepted.onclick = calculateSaveValidity;
        function addPlayer(id) {
            var node = document.querySelector('#addPlayer option[value="' + id + '"]');
            addPlayerSelect.removeChild(node);
            removePlayerSelect.appendChild(node);
            players.push(id);
            playerField.value = JSON.stringify(players);
            var player = getPlayer(id);
            node = document.createElement('li');
            node.setAttribute("userId", id);
            node.innerHTML = '<b>' + player.alias + '</b> <a href="mailto:' + player.email + '">' + player.email + '</a>';
            playerList.appendChild(node);
            addPlayerSelect.options.selectedIndex = 0;
            removePlayerSelect.options.selectedIndex = 0;
            calculateSaveValidity();
        }
        function removePlayer(id) {
            var node = document.querySelector('#removePlayer option[value="' + id + '"]');
            removePlayerSelect.removeChild(node);
            addPlayerSelect.appendChild(node);
            var idx = players.indexOf(id);
            if (idx != -1) players.splice(idx, 1);
            playerField.value = JSON.stringify(players);
            node = document.querySelector('ul.Players li[userid="' + id + '"]');
            playerList.removeChild(node);
            addPlayerSelect.options.selectedIndex = 0;
            removePlayerSelect.options.selectedIndex = 0;
            calculateSaveValidity();
        }
        if (addPlayerSelect) {
            addPlayerSelect.onchange = function(evt) {addPlayer(parseInt(evt.target.value))};
            removePlayerSelect.onchange = function(evt) {removePlayer(parseInt(evt.target.value))};
            for (var i = 0; i < users.length; i++) {
                var user = users[i];
                var option = document.createElement('option');
                option.setAttribute("value", user.id);
                option.setAttribute("title", user.rankName);
                option.innerHTML = user.alias;
                addPlayerSelect.appendChild(option);
            }
            document.querySelector('input.Filter').onkeyup = function(evt) {
                var substr = evt.target.value;
                var test = substr ? new RegExp('\\b' + substr.replace(/[^a-z0-9_ ]/gi, function(s) {return '\\' + s}),'i') : {test: function(){return true}};
                for (var i = 1; i < addPlayerSelect.options.length; i++){
                    var option = addPlayerSelect.options[i];
                    option.style.display= test.test(option.text) ? '' : 'none';
                }
            }
            var p = players;
            players = [];
            if (p) for (var i = 0; i < p.length; i++) addPlayer(p[i]);
        }
        drawAwards(state);
        if (state != 'Recruiting' && type == 'Elimination')
        {
            drawEliminationTable();
            drawPanel.style.display = '';
            document.querySelectorAll('tr.Players,tr.PlayerManager,tr.PersonalManager').forEach(node => {node.style.display='none';})
        }
        if (state != 'Recruiting' && type == 'Pyramid') {
            drawPyramid();
            saveButton.disabled = true;
        }
        stateSelect.onchange = function(evt) {
            calculateSaveValidity();
            if (state == 'Recruiting' && this.selectedIndex == 1 && type == 'Elimination') {
                addPlayerSelect.disabled = removePlayerSelect.disabled = true;
                drawPanel.style.display = '';
                drawAccepted.parentNode.style.display = '';
                drawEliminationTable();
            } else if (this.selectedIndex == 0) {
                drawPanel.style.display = 'none';
                addPlayerSelect.disabled = removePlayerSelect.disabled = false;
                if (state == 'Recruiting') document.querySelector('input[name="draw"]').value = '';
            }
        }
    }
    window.addEventListener('load', setUp);
}

function drawAwards(state) {
    var destination = document.querySelector('div.Awards');
    var settings = JSON.parse(document.querySelector('#awards').value);
    for (var i = 1; i <= 4; i++) {
        var id = i == 4 ? 90 : i;
        var award = settings[id];
        if (state != 'Recruiting' && !award.url) continue;
        var node = document.createElement('div');
        node.className='Award Medium';
        node.innerHTML = [
            '<img src="' + (award.url || '/images/missing.png') + '">',
            '<span>' + award.type + '</span>',
            i != 4 && award.player ? '<a href="/user.php?id=' + award.player + '">' + playerLU[award.player] + '</a>' : '',
            state == 'Recruiting' ? '<input type="file" name="award' + id + '" value="Upload image">' : ''
        ].join('');
        destination.append(node);
    }
}

function drawEliminationTable() {
    var draw = document.querySelector('input[name="draw"]');
    var depth = Math.ceil(Math.log2(players.length));
    if (draw.value) {
        var complete = [];
        var result;
        var pattern = /(\d+)-(\d+):(.):(\d+)-(BYE|\d+)(:\d+)?/g;
        while(result = pattern.exec(draw.value))
            complete.push({round: parseInt(result[1]), playOff: parseInt(result[2]), victor: parseInt(result[3]), a: parseInt(result[4]), b:result[5] == 'BYE' ? 'BYE' : parseInt(result[5]), battleId: result[6] && result[6].slice(1)});
    } else {
        var numPairs = Math.pow(2, depth - 1);
        var pairs = [];
        for (var i = 0; i < numPairs; i++) pairs.push({a: null, b: null, round: 1, victor: 0, playOff: i});
        for (var i = players.length; i < 2 * numPairs; i++)
            while (true)
            {
                var choice = pairs[Math.floor(pairs.length*Math.random())];
                if (choice.b) continue;
                choice.b = 'BYE';
                break;
            }
        var complete = [];
        var playerIds = players.slice();
        while (pairs.length) {
            var player = playerIds.splice(Math.floor(Math.random() * playerIds.length), 1)[0];
            var idx = Math.floor(Math.random() * pairs.length);
            var pair = pairs[idx];
            if (!pair.a)
                pair.a = player;
            else
                pair.b = player;
            if (!pair.b) continue;
            if (pair.b == 'BYE') pair.victor = 1;
            complete.push(pair);
            pairs.splice(idx, 1);
        }
        complete.sort((a,b) => {return a.round == b.round ? a.playOff - b.playOff : (a.round - b.round)});
    }
    var rows = [];
    var pairings = {};
    function calcResult(pair, player) {return 'Player ' + (pair.victor == 0 ? 'Ongoing' : player == pair['ab'.charAt(pair.victor-1)] ? 'Won' : 'Lost');}
    for (var i = 0; i < complete.length; i++) {
        var pair = complete[i];
        if (pair.round == 1) {
            rows.push([{c: 'Blank'}, {rowspan: 2, player: pair.a, c: calcResult(pair, pair.a), battleId: pair.battleId}]);
            rows.push([{c: 'Blank'}]);
            rows.push([{c: 'Blank'}, {rowspan: 2, player: pair.b, c: calcResult(pair, pair.b), battleId: pair.battleId}]);
            rows.push([{c: 'Blank'}]);
            pairings["1-" + pair.playOff] = pair;
        } else {
            pairings[pair.round + '-' + pair.playOff] = pair;
        }
    }
    for (var d = 1; d <= depth; d++) {
        var spacing = Math.pow(2, d - 1);
        rows[0].push({c: 'Blank', rowspan: spacing, level: d + 'b'});
        row = spacing;
        for (row = spacing; row < rows.length; row+= 4 * spacing) {
            rows[row].push({c: 'Group', rowspan: 2 * spacing, level: d + 'b'});
            if (rows[row + 2 * spacing]) rows[row + 2 * spacing].push({c: 'Blank', rowspan: row + spacing < rows.length ? 2 * spacing : spacing, level: d + 'b'});
        }
        rows[0].push({c: 'B', rowspan: spacing * 2, level: d + 'a'});
        for (var row = spacing * 2; row + spacing * 4 < rows.length; row+= spacing * 4)
            rows[row].push({c: 'B', rowspan: spacing * 4, level: d + 'a'});
        rows[rows.length - spacing * 2].push({c: 'Blank', rowspan: spacing * 2, level: d + 'a'});

        rows[0].push({c: 'Blank', rowspan: row = Math.pow(2, d) - 1, level: d});
        var step = Math.pow(2, d + 1) - 2;
        var playOff = 0;
        while (true) {
            var pair = pairings[(d + 1) + '-' + playOff];
            var previousA = pairings[d + '-' + (2 * playOff)];
            if (!pair) {
                var previousB = pairings[d + '-' + (2 * playOff + 1)];
                if (previousA && previousB && (previousA.victor || previousB.victor)) {
                    pair = pairings[(d + 1) + '-' + playOff] = {
                        round: d + 1,
                        a: previousA[['x', 'a', 'b'][previousA.victor]],
                        b: previousB[['x', 'a', 'b'][previousB.victor]],
                        victor: 0
                    }
                    if (pair.a && pair.b) complete.push(pair);
                }
            } else if (previousA) {
                if (pair.b == previousA.a || pair.b == previousA.b)
                    pair = {a: pair.b, b: pair.a, round: pair.round, victor: pair.victor ? 3 - pair.victor : 0}
            }
            if (pair) pair.playOff = playOff;
            if (d == depth && previousA && previousA.victor)
                rows[row].push({c: 'Player Won', rowspan: 2, player: previousA['ab'.charAt(previousA.victor - 1)], level: d})
            else
                rows[row].push({c: pair ? calcResult(pair, pair.a) : 'Player', rowspan: 2, player: pair && pair.a, level: d, battleId: pair && pair.battleId});
            row += 2;
            //if (row >= rows.length) break;
            rows[row].push({c: 'Blank', rowspan: step, level: d});
            row += step
            if (row >= rows.length) break;
            rows[row].push({c: pair ? calcResult(pair, pair.b) : 'Player', rowspan: 2, player: pair && pair.b, level: d, battleId: pair && pair.battleId});
            row += 2;
            if (row >= rows.length) break;
            if (row + spacing - 1 > rows.length) {
                rows[row].push({c: 'Blank', rowspan: spacing - 1, level:d});
                break;
            }
            rows[row].push({c: 'Blank', rowspan: step, level: d});
            row += step
            if (row >= rows.length) break;
            playOff ++;
        }
    }
    var heading = ['<tr>', '<td><div class="Space"></div></td>'];
    for (var i = 0; i <= depth; i++) {
        if (i) heading.push('<td colspan="2"><div class="Space"></div></td>');
        var finals = i + 3 - depth;
        heading.push('<th>' + (finals < 0 ? 'Round ' + (i + 1) : ['Quarter-Finals','Semi-Finals','Finals','Victor'][finals]) + '</th>');
    }
            
    document.querySelector('.EliminationCanvas').innerHTML = heading.join('') + '</tr>' + 
        rows.map(function(row) {
            return '<tr>' + row.map(function(cell) {
                var contents = '<div class="Space"></div>';
                if (cell.player) {
                    contents = '<span class="Player">BYE</span>';
                    for (var i = 0; i < users.length; i++)
                        if (users[i].id == cell.player) {
                            var url = cell.battleId ? 'battle.php?id=' + cell.battleId : ('user.php?id=' + cell.player);
                            contents = '<a href="' + url + '" class="Player">' + users[i].alias + '</a>';
                            break;
                        }
                } else if (cell.c == 'Player')
                    contents = '<span class="Player">&nbsp;</span>';
                return '<td class="' + cell.c + '" rowspan="' + (cell.rowspan || 1) + '" level="' + cell.level + '">' + contents + '</td>';
            }).join('') + '</tr>';
        }).join('');

    complete.sort((a,b) => {return a.round == b.round ? a.playOff - b.playOff : (a.round - b.round)});
    draw.value = complete.map(p => p.round + '-' + p.playOff + ':' + p.victor + ':' + p.a + '-' + p.b + (p.battleId ? ':' + p.battleId : '')).join(',');
    drawConsolationRound(pairings[depth + '-1']);
}
function drawConsolationRound(pairing)
{
    if (!pairing) return;
    var victor = pairing.victor && (pairing.victor == 1 ? pairing.a : pairing.b);
    var classA = victor == pairing.a ? 'Player Won' : !victor ? 'Player': 'Player Lost';
    var classB = victor == pairing.b ? 'Player Won' : !victor ? 'Player': 'Player Lost';
    document.querySelector('table.ConsolationCanvas').innerHTML = [
        '<tr><td><div class="Space"></td><th>Consolation</th><td colspan="2"><div class="Space"></div></td><th>Third</th></tr>',
        '<tr>',
            '<td class="Blank"><div class="Space"></div></td>',
            '<td rowspan="2" class="' + classA + '"><a href="/battle.php?id=' + pairing.battleId + '">' + playerLU[pairing.a] + '</a></td>',
            '<td class="Blank"><div class="Space"></div></td>',
            '<td class="B" rowspan="2"><div class="Space"></div></td>',
            '<td class="Blank"><div class="Space"></div></td>',
        '</tr>',
        '<tr>',
            '<td class="Blank"><div class="Space"></div></td>',
            '<td class="Group" rowspan="2" level="1b"><div class="Space"></div></td>',
            victor ?
                '<td rowspan="2" class="Player Won"><a href="/user.php?id=' + victor + '">' + playerLU[victor] + '</a></td>' :
                '<td rowspan="2" class="Player"><div class="Space"></div></td>',
        '</tr>',
        '<tr>',
            '<td class="Blank"><div class="Space"></div></td>',
            '<td rowspan="2" class="' + classB + '"><a href="/battle.php?id=' + pairing.battleId + '">' + playerLU[pairing.b] + '</a></td>',
            '<td class="Blank"><div class="Space"></div></td>',
            '<td class="Blank" colspan="3"><div class="Space"></div></td>',
        '</tr>',
        '<tr>',
            '<td class="Blank"><div class="Space"></div></td>',
            '<td class="Blank" colspan="4"><div class="Space"></div></td>',
        '</tr>'].join('');
}

var validNumber = /^(0|[1-9][0-9]*)$/;
function validateEndBattle() {
    function enableSave() {
        //if (readonly) return;
        var disabled = false;
        var allPoints = document.querySelectorAll('input.Points');
        for (var i = 0; i < allPoints.length; i++)
        {
            var node = allPoints[i];
            var valid = validNumber.test(node.value);
            if (valid)
                node.className = 'Points';
            else
            {
                node.className = 'Points Error';
                disabled = true;
            }
        }
        var allResults = document.querySelectorAll('select.Results');
        for (var i = 0; i < allResults.length; i++)
        {
            if (allResults[i].options.selectedIndex <= 0)
                disabled = true;
        }
        document.querySelector('input[type="submit"]').disabled = disabled;
    }
    window.addEventListener('load', function() {
        var allPoints = document.querySelectorAll('input.Points');
        for (var i = 0; i < allPoints.length; i++)
        {
            allPoints[i].onkeyup = enableSave;
            if (readonly) allPoints[i].disabled = true;
        }
        var allResults = document.querySelectorAll('select.Results');
        for (var i = 0; i < allResults.length; i++)
        {
            var node = allResults[i];
            node.onchange = enableSave;
            if (readonly) node.disabled = true;
            var last = node.options[node.options.length - 1];
            node.removeChild(last);
            node.insertBefore(last, node.options[2]);   // Lost after first
        }
        enableSave();

    });

}
function drawPyramid() {
    document.querySelector('tr.Players').style.display='none';
    (document.querySelector('tr.PlayerManager') || {style:{}}).style.display = 'none';
    var rows = [];
    var userBattles = {};
    for (var battleId in battles)
        for (var i = 0, userList = battles[battleId]; i < userList.length; i++)
            userBattles[userList[i]] = battleId;
    for (var userId in levels) {
        var level = levels[userId] - 1;
        while (level >= rows.length) rows.push([]);
        var row = rows[level];
        row.push({userId: userId, alias: playerLU[userId], battle: Boolean(userBattles[userId])});
    }
    var myLevel = levels[me] ? levels[me] - 1 : 0;
    var iMayChallenge = [];
    if (levels[me] && !userBattles[me]) {
        for (var i = 0, playersOnMyLevel = rows[myLevel]; i < playersOnMyLevel.length; i++)
            if (!playersOnMyLevel[i].battle && playersOnMyLevel[i].userId != me)
                iMayChallenge.push(playersOnMyLevel[i].userId);
        if (!iMayChallenge.length && myLevel > 1)
            for (var i = 0, playersOnMyLevel = rows[myLevel - 1]; i < playersOnMyLevel.length; i++)
                if (!playersOnMyLevel[i].battle && playersOnMyLevel[i].userId != me)
                    iMayChallenge.push(playersOnMyLevel[i].userId);
    }
    rows.reverse()
    var html = ['<h2>Pyramid</h2>'];
    for (var i = 0; i < rows.length; i++)
        html.push((i ? '<br>' : '') + '<div class="Level' + i + '">' +
            rows[i].map(o => '<div class="Player"><a href=/user.php?id=' + o.userId + '">' + o.alias + '</a>' +
                (iMayChallenge.indexOf(o.userId) >= 0 ? '<button player="' + o.userId + '" class="Challenge" title="Challenge ' + 
                    o.alias + '"></button>' : '') + '</div>').join('') +
            '</div>');
    var first = true;
    for (var battleId in battles) {
        if (first) {
            first = false;
            html.push('<h2>Battles</h2><ul>');
        }
        html.push('<li><a href="/battle.php?id=' + battleId + '">' +
            battles[battleId].map(userId => playerLU[userId]).join (' v ') + '</a></li>');
    }

    var target = document.querySelector('.PyramidPanel');
    target.style.display = '';
    target.innerHTML = html.join('');
    for (var i = 0, challenges = document.querySelectorAll('button.Challenge'); i < challenges.length; i++)
        challenges[i].onclick = function(evt) {document.location='/challenge.php?tournamentId=' + tournamentId + '&from=' + me + '&to=' + this.getAttribute('player'); return false;}
}


function setBattleFilters()
{
    window.addEventListener('load', function() {
        var state = document.querySelector('select.StatusFilter');
        var type = document.querySelector('select.TypeFilter');
        var tournament = document.querySelector('select.TournamentFilter');
        for (var i = 0; i < statuses.length; i++)
        {
            var option = document.createElement('option');
            option.innerHTML = statuses[i];
            state.appendChild(option);
        }
        for (var i = 0; i < types.length; i++)
        {
            var option = document.createElement('option');
            option.innerHTML = types[i];
            type.appendChild(option);
        }
        for (var i = 0; i < tournaments.length; i++) {
            var option = document.createElement('option');
            option.innerHTML = 'the ' + tournaments[i].name;
            option.setAttribute('value', tournaments[i].id);
            tournament.appendChild(option);
        }
        for (var i = 0, rows = document.querySelectorAll('table.Battles tbody tr'); i < rows.length; i++) {
            var row = rows[i];
            row.showName = row.showType = row.showState = row.showTournament = true;
        }
        function display(row) {
                row.style.display = row.showName && row.showState && row.showType && row.showTournament ? '' : 'none';
        }
        state.onchange = function()
        {
            var newStatus = state.options[state.options.selectedIndex].text;
            for (var i = 0, nodes = document.querySelectorAll('td.State'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode;
                row.showState =  (newStatus == 'All' || nodes[i].innerHTML.trim() == newStatus);
                display(row);
            }
        }
        type.onchange = function()
        {
            var newType = type.options[type.options.selectedIndex].text;
            for (var i = 0, nodes = document.querySelectorAll('td.Type'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode;
                row.showType =  (newType == 'All' || nodes[i].innerHTML.trim() == newType);
                display(row);
            }
        }
        var filter = document.querySelector('input.TextFilter');
        filter.onkeyup = function(){
            var patt = new RegExp('\\b' + filter.value, 'i');
            for (var i = 0, nodes = document.querySelectorAll('td.Name a'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode.parentNode;
                row.showName =  patt.test(nodes[i].innerHTML);
                display(row);
            }
        }
        tournament.onchange = function() {
            var newTournament = tournament.options[tournament.options.selectedIndex].value;
            for (var i = 0, nodes = document.querySelectorAll('td.Tournament'); i < nodes.length; i++) {
                var row = nodes[i].parentNode;
                var rowTournament = nodes[i].getAttribute('sorttable_customkey');
                row.showTournament = (newTournament == 'Any') || (newTournament == 'None' && rowTournament == '0') || (newTournament == rowTournament);
                display(row);
            }
        }
    });
}
function setTournamentFilters()
{
    window.addEventListener('load', function() {
        var state = document.querySelector('select.StatusFilter');
        var type = document.querySelector('select.TypeFilter');
        var gametype = document.querySelector('select.GameTypeFilter');
        for (var i = 0; i < statuses.length; i++)
        {
            var option = document.createElement('option');
            option.innerHTML = statuses[i];
            state.appendChild(option);
        }
        for (var i = 0; i < types.length; i++)
        {
            var option = document.createElement('option');
            option.innerHTML = types[i];
            type.appendChild(option);
        }
        for (var i = 0; i < gametypes.length; i++) {
            var option = document.createElement('option');
            option.innerHTML = gametypes[i];
            gametype.appendChild(option);
        }
        for (var i = 0, rows = document.querySelectorAll('table.Tournaments tbody tr'); i < rows.length; i++) {
            var row = rows[i];
            row.showName = row.showType = row.showState = row.showGameType = true;
        }
        function display(row) {
                row.style.display = row.showName && row.showState && row.showType && row.showGameType ? '' : 'none';
        }
        state.onchange = function()
        {
            var newStatus = state.options[state.options.selectedIndex].text;
            for (var i = 0, nodes = document.querySelectorAll('td.State'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode;
                row.showState =  (newStatus == 'All' || nodes[i].innerHTML.trim() == newStatus);
                display(row);
            }
        }
        type.onchange = function()
        {
            var newType = type.options[type.options.selectedIndex].text;
            for (var i = 0, nodes = document.querySelectorAll('td.Type'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode;
                row.showType =  (newType == 'All' || nodes[i].innerHTML.trim() == newType);
                display(row);
            }
        }
        gametype.onchange = function()
        {
            var newGameType = gametype.options[gametype.options.selectedIndex].text;
            for (var i = 0, nodes = document.querySelectorAll('td.GameType'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode;
                row.showType =  (newGameType == 'All' || nodes[i].innerHTML.trim() == newGameType);
                display(row);
            }
        }
        var filter = document.querySelector('input.TextFilter');
        filter.onkeyup = function(){
            var patt = new RegExp('\\b' + filter.value, 'i');
            for (var i = 0, nodes = document.querySelectorAll('td.Name a'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode.parentNode;
                row.showName =  patt.test(nodes[i].innerHTML);
                display(row);
            }
        }
    });
}
function setUserFilters()
{
    window.addEventListener('load', function() {
        var filter = document.querySelector('input.TextFilter');
        filter.onkeyup = function(){
            var patt = new RegExp('\\b' + filter.value, 'i');
            var emails = document.querySelectorAll('td.Email a');
            for (var i = 0, nodes = document.querySelectorAll('td.Alias a'); i < nodes.length; i++)
            {
                var row = nodes[i].parentNode.parentNode;
                row.style.display = patt.test(nodes[i].innerHTML) || patt.test(emails[i].innerHTML) ? '' : 'none';
            }
        }
    });
}

function validateRequiredString(node)
{
    node.className = node.className.replace(/ ?\bError\b/, '');
    if (node.value.trim()) return true;
    node.className += ' Error';
    return false;
}
function validateCardinal(node)
{
    node.className = node.className.replace(/ ?\bError\b/, '');
    if (/^(0|[1-9]\d*)$/.test(node.value)) return true;
    node.className += ' Error';
    return false;
}
function validateDate(node)
{
    node.className = node.className.replace(/ ?\bError\b/g, '');
    var parts = /^(\d\d\d\d)-(\d\d)-(\d\d)$/.exec(node.value);
    if (parts)
    {
        var date = parseInt(parts[3].replace(/^0/, ''));
        var month = parseInt(parts[2].replace(/^0/, '')) - 1;
        var year = parseInt(parts[1].replace(/^0{1,3}/, ''));
        var dt = new Date(year, month, date);
        if (dt.getDate() == date && dt.getMonth() == month && dt.getFullYear() == year)
            return true;
    }
    node.className += ' Error';
    return false;
}

function loadPlayerEditor()
{
    window.addEventListener('load', function()
    {
        loadDateEditor(document.querySelector('input[name="created"]'));
        var save = document.querySelector('input[type="submit"]');
        function validate()
        {
            save.disabled = true;
            if (!(validateRequiredString(inputs.alias) && validateRequiredString(inputs.email) && validateRequiredString(inputs.pikaday_created))) return;
            if (inputs.points && !(validateCardinal(inputs.points) && validateCardinal(inputs.battles) && validateCardinal(inputs.victories))) return;
            if (!/^.+@.+\...+$/.test(inputs.email.value))
                return inputs.email.className += ' Error';
            if (newUser && !inputs.password.value)
                return inputs.password.className += ' Error';
            if (inputs.password.value != inputs['confirm'].value)
                return inputs['confirm'].className += ' Error';
            save.disabled = false;
        }
        var inputs = {pikaday_created: document.getElementById('pikaday_created')}
        for (var i = 0, all = document.querySelectorAll('input'); i < all.length; i++)
            if (all[i].name)
                inputs[all[i].name] = all[i];
        inputs.alias.onkeyup = inputs.email.onkeyup = inputs.created.onkeyup = function(evt) {if (validateRequiredString(evt.target)) validate()};
        if (inputs.battles)
            inputs.battles.onkeyup = inputs.victories.onkeyup = inputs.points.onkeyup = function(evt) {if (validateCardinal(evt.target)) validate()};
        inputs.password.onkeyup = inputs['confirm'].onkeyup = function(evt) {evt.target.className = evt.target.className.replace(/ ?\bError\b/g, ''); validate()};
        validate();
    });
}
function loadDateEditor(node)
{
    var dt = node.value && new Date(parseInt(node.value.slice(0,4)), parseInt(node.value.slice(5,7).replace(/^0/, '')) - 1, parseInt(node.value.slice(8,10).replace(/^0/, '')));
    node.type = 'hidden';
    var displayNode = document.createElement('input');
    displayNode.className = 'Pikaday';
    displayNode.id = 'pikaday_' + node.name;
    node.parentNode.insertBefore(displayNode, node);
    var pd = new Pikaday({field: displayNode, onSelect: function () {
        dt = pd.getDate();
        node.value = String(dt.getFullYear()) + '-' + /..$/.exec('0' + (dt.getMonth() + 1))[0] + '-' + /..$/.exec('0' + dt.getDate())[0];
    }});
    if (dt) pd.setDate(dt);
}
