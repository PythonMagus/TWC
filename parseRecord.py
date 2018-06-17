from HTMLParser import HTMLParser
from htmlentitydefs import name2codepoint

import os, sys, re

TABLE = re.compile(r'table\d')
NAMES = re.compile(r'(\w.*\w) ?\((.*)\)', re.I)
ELEMENT_LABEL = re.compile(r'\belement-label\b')
DATE = re.compile(r'(\d{1,2})/(\d{1,2})/(\d{4})')
ADMIN = re.compile('(Lordlau1|HHFD50|Lancier|Marius|Python Magus)')
TOURNAMENT = re.compile(r'TWC Tournament (.*) \((.*)\)')

users = [{'unid': 1, 'alias': 'bombur', 'fullname': 'bombur', 'email': 'afenelon@terra.com.br', 'started': '2018-01-01', 'admin': 0}]
userLU = {'bombur': 1}
gametypes = {
    "GD1938": 3,
    "PnS: C": 16,
    'P&S: C': 16,
    "ATG": 1,
    "GGWitW": 9,
    "SC WWII": 11,
    "OB": 13,
    "FC: RS": 12,
    "FoG II": 8,
    "MtG": 14,
    "G:TTT": 15,
    "PoN": 6,
    "WoN": 2,
    "WoS": 4,
    "CW2": 5,
    "EAW": 25,
    "RUS": 19,
    "TYW": 22,
    "NCP": 23,
    "ECW": 20,
    "AJE": 7,
    "Espana": 21,
    "BoAII:WiA": 24,
    "JTS": 26,
    "HW: LG": 41,
    'SJ: SoS': 17,
    'RoP': 18
}
BATTLE = re.compile(r'(' + '|'.join(gametypes.keys()) + ') ?(\S.*)')
aliases = {
    'pyhtonmagus': 'python magus',
    'python': 'python magus',
    'tyler': 'tyrannical tyler',
    'zardoz': 'zardoz03',
    'armored_lion': 'armoured lion',
    'armoured_lion': 'armoured lion',
    'marian': 'maars85',
    'hohenstaufen': 'hohen staufen',
    'hohenstaufen234': 'hohen staufen',
    'andrew': 'andrew camara',
    'pierro.ferdinand': 'pierro_f',
    'klaus varna': 'klaus-varna',
    'klausvarna': 'klaus-varna',
    'ournorthernneighbor': 'north neighbor',
    'pyyrhos': 'pyrrhos',
    'duane': 'duane clark',
    'olaf': 'schmolywar',
    'mike': 'dralmar',
    'charles': 'c10sutton',
    'lordlau': 'lordlau1',
    'sabac': 'sabac.red',
    
}
battles = []
tournaments = []

class SpreadsheetParser(HTMLParser):
    def __init__(self):
        HTMLParser.__init__(self)
        self.state = None
        self.next = None
        self.rec = None
        self.txt = None
        self.expectingInput = 0
        self.pickOption = False
        self.output = None
        self.inOption = False
        self.startRow = False
        self.numAwards = 0

    def handle_data(self, data):
        if self.txt != None:
            self.txt += data
        if self.inOption:
            self.choices.append(data)

    def handle_entityref(self, name):
        if self.txt != None:
            self.txt += chr(name2codepoint[name])

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        className = attrs['class'] if 'class' in attrs else '';
        _id = attrs['id'] if 'id' in attrs else '';
        if tag == 'a' and 'name' in attrs and TABLE.match(attrs['name']):
            self.state = attrs['name']
            if self.state == 'table0':
                print 'DELETE FROM users;\nDELETE FROM userribbons;'
                print 'INSERT INTO users (id, realName, alias, created, `password`, admin, email) VALUES ' + \
                    "(%(unid)s, '%(fullname)s','%(alias)s','%(started)s', 'hidden%(unid)s', %(admin)s, '%(email)s');" % users[0]
            elif self.state == 'table2':
                print 'DELETE FROM battles;\nDELETE FROM userbattles;'
                for alias, name in aliases.items():
                    userLU[alias] = userLU[name]
                self.skipRows = 3
                print 'ALTER TABLE users AUTO_INCREMENT = %d;' % (len(users) + 1)
                print 'ALTER TABLE battles AUTO_INCREMENT = 1; ALTER TABLE userbattles AUTO_INCREMENT = 1;'
            elif self.state == 'table3':
                for tbl in 'tournaments,tournamentbattles,tournamentusers,tournamentawards'.split(','):
                    print "DELETE FROM %s; ALTER TABLE %s AUTO_INCREMENT = 1;" % (tbl,tbl)
                self.skipRows = 2
        elif tag == 'td':
            if self.state == 'table0':
                self.parseUsers(tag, attrs)
            elif self.state == 'table2':
                self.parseBattles(tag, attrs)
            elif self.state == 'table3':
                self.parseTournaments(tag, attrs)
        elif tag == 'tr':
            if self.state == 'table2':
                self.parseBattles(tag, attrs)
            elif self.state == 'table3':
                self.parseTournaments(tag, attrs)

    def parseUsers(self, tag, attrs):
        if tag == 'td':
            if 'height' in attrs and attrs['height'] == '227' and self.next == None:
                self.rec = {}
                self.next = 'names'
                self.txt = ''
            elif 'sdnum' in attrs and attrs['sdnum'] == "3081;0;DD/MM/YYYY" and self.next == 'Start':
                self.txt = ''

    def parseTournaments(self, tag, attrs):
        if tag == 'tr':
            if self.skipRows > 0:
                self.skipRows -= 1
            else:
                self.startRow = True
        elif tag == 'td':
            if self.startRow:
                self.startRow = False
                if 'colspan' in attrs and attrs['colspan'] == '5':
                    self.next = 'Tournament'
                else:
                    self.rec = {}
                    self.next = 'name'
            self.txt = ''

    def parseBattles(self, tag, attrs):
        if tag == 'tr':
            if self.skipRows > 0:
                self.skipRows -= 1
            else:
                self.startRow = True
        elif tag == 'td':
            if self.startRow:
                self.startRow = False
                if 'colspan' in attrs:
                    self.next = None
                else:
                    self.rec = {}
                    self.next = 'name'
            self.txt = ''

    def completeUsers(self, tag):
        if self.txt != None and self.rec != None and tag == 'td' and self.next:
            if self.next == 'names':
                result = NAMES.search(self.txt)
                if not result:
                    self.rec['alias'] = self.rec['fullname'] = self.txt
                    self.rec['admin'] = 0
                else:
                    self.rec['alias'] = result.group(1)
                    self.rec['fullname'] = result.group(2)
                    self.rec['admin'] = 1 if ADMIN.match(result.group(1)) else 0
                self.next = 'Start'
                self.txt = None
            elif self.next == 'Start':
                result = DATE.match(self.txt)
                if result:
                    self.rec['started'] = '%s-%s-%s' % (result.group(3), result.group(2), result.group(1))
                    users.append(self.rec)
                    alias = self.rec['alias'].lower()
                    if ' ' in alias:
                        aliases[alias.replace(' ', '')] = alias
                    userLU[alias] = self.rec['unid'] = len(users)
                    print 'INSERT INTO users (id, realName, alias, created, `password`, admin, email) VALUES ' + \
                        "(%(unid)s, '%(fullname)s','%(alias)s','%(started)s', 'hidden%(unid)s', %(admin)s, 'unknown@email');" % self.rec
                self.rec = None
                self.txt = None
                self.next = None

    def completeTournament(self,tag):
        if tag != 'td' or self.next == None:
            return
        if self.next == 'Tournament':
            result = TOURNAMENT.match(self.txt)
            if result:
                tournaments.append(result.group(1))
                gametypeid = gametypes[result.group(2)] if result.group(2) in gametypes else 0
                self.tournamentId = len(tournaments)
                print 'INSERT INTO tournaments (id, gametypeid, name, started, type, state) VALUES ' + \
                    "(%d, %d, '%s', '2018-01-01', 0, 1);" % (self.tournamentId, gametypeid, result.group(1))
                print 'INSERT INTO tournamentawards (tournamentId, level, url) VALUES ' +\
                    "(%d, 1, 'x'), (%d, 2, 'x'), (%d, 3, 'x'), (%d, 90, 'x');" % ((self.tournamentId, ) * 4)
                self.numAwards += 4
                self.round = 1
                self.playOff = -1
                self.players = []
                self.tournamentState = 'reading'
            else:
                print "-- WARNING - Unknown Tournament - " + self.txt
        else:
            last = self.next == 'Winners'
            self.completeBattle(tag)
            if last:
                battle = battles[-1]
                players = []
                nextRound = False
                for playerRec in battle['players'].values():
                    players.append(playerRec['unid'])
                    if playerRec['unid'] in self.players:
                        nextRound = True
                if tournaments[-1][:2] == '#1':
                    self.playOff += 1
                    for player in players:
                        if player not in self.players:
                            self.players.append(player)
                            print 'INSERT INTO tournamentusers (tournamentId, userId, awardId) VALUES ' +\
                                "(%d, %d, %d);" % (self.tournamentId, player, self.numAwards)
                elif nextRound:
                    if self.round == 1:
                        for player in self.players:
                            print 'INSERT INTO tournamentusers (tournamentId, userId, awardId) VALUES ' +\
                                "(%d, %d, %d);" % (self.tournamentId, player, self.numAwards)
                    self.players = players
                    self.round += 1
                    if self.playOff == 1 and self.tournamentState == 'reading':
                        self.tournamentState = 'loading'
                        for playerRec in battle['players'].values():
                            if playerRec['result'] == 1:
                                print 'UPDATE tournamentusers SET awardId = %d WHERE tournamentId = %d AND userId = %d;' % \
                                    (self.numAwards - 1, self.tournamentId, playerRec['unid'])
                        self.playOff = 1
                    else:
                        self.playOff = 0
                else:
                    self.playOff += 1
                    self.players.extend(players)
                    if self.tournamentState == 'loading':
                        self.tournamentState = None
                        self.playOff = 0
                        winner = second = 0
                        for playerRec in battle['players'].values():
                            if playerRec['result'] == 1:
                                winner = playerRec['unid']
                            elif playerRec['result'] == 99:
                                second = playerRec['unid']
                        if winner:
                            print 'UPDATE tournamentusers SET awardId = %d WHERE tournamentId = %d AND userId = %d;' % \
                                (self.numAwards - 3, self.tournamentId, winner)
                        if second:
                            print 'UPDATE tournamentusers SET awardId = %d WHERE tournamentId = %d AND userId = %d;' % \
                                (self.numAwards - 2, self.tournamentId, second)
                                
                print 'INSERT INTO tournamentbattles (tournamentId, battleId, round, playOff) VALUES ' +\
                    "(%d, %d, %d, %d);" % (self.tournamentId, battle['unid'], self.round, self.playOff)


    def completeBattle(self, tag):
        if tag != 'td' or self.next == None:
            return
        if self.next == 'name':
            result = BATTLE.match(self.txt)
            if not result:
                print "-- ERROR: Unknown battle " + self.txt
                self.next = None
            else:
                self.rec = {
                    'fullname': self.txt, 
                    'type': gametypes[result.group(1)],
                    'started': "'2018-01-01'",
                    'ended': 'null',
                    'name': result.group(2).replace("'","\\'")}
                self.next = 'Players'
        elif self.next == 'Players':
            players = map(lambda s: s.strip().lower(), self.txt.split('/'))
            self.rec['players'] = {}
            for player in players:
                if player in userLU:
                    self.rec['players'][userLU[player]] = {'result': 0, 'points': 0, 'unid': userLU[player]}
                else:
                    print '-- ERROR: I do not know about player %s in %s' % (player, self.rec['fullname'])
            self.next = 'Winners'
        elif self.next == 'Winners':
            if not self.txt:
                self.rec['state'] = 1
            elif self.txt == 'x':
                self.rec['state'] = 3   # cancelled
            else:
                for playerRec in self.rec['players'].values():
                    playerRec['result'] = 99 # lost
                self.rec['state'] = 2
                self.rec['ended'] = "'2018-02-01'"
                players = map(lambda s: s.strip().lower(), self.txt.replace(',', '/').split('/'))
                lu = self.rec['players']
                for player in players:
                    if player in userLU:
                        rec = self.rec['players'][userLU[player]]
                        rec['result'] = 1
                        rec['points'] = 1
                    else:
                        print '-- ERROR: I do not know about player %s in %s' % (player, self.rec['fullname'])
            self.next = None
            battles.append(self.rec)
            self.rec['unid'] = len(battles)
            print "INSERT INTO battles (id, typeid, name, state, started, ended) VALUES " + \
                "(%(unid)d,%(type)d,'%(name)s',%(state)d,%(started)s,%(ended)s);" % self.rec
            for playerRec in self.rec['players'].values():
                print "INSERT INTO userbattles (userId, battleId, result, points) VALUES " + \
                    ("(%(unid)d,%%d,%(result)d,%(points)d);" % playerRec) % self.rec['unid']

    def handle_endtag(self, tag):
        if tag != 'td':
            return
        elif self.state == 'table0':
            self.completeUsers(tag)
        elif self.state == 'table2':
            self.completeBattle(tag)
        elif self.state == 'table3':
            self.completeTournament(tag)

class EMailParser(HTMLParser):
    def __init__(self):
        HTMLParser.__init__(self)
        self.state = None
        self.txt = None

    def handle_data(self, data):
        if self.txt != None:
            self.txt += data

    def handle_entityref(self, name):
        if self.txt != None:
            self.txt += chr(name2codepoint[name])

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == 'p':
            self.state = 'line'
        elif self.state == 'line' and tag == 'a':
            self.txt = ''

    def handle_endtag(self, tag):
        if not self.state:
            return
        elif tag == 'a' and self.state == 'line':
            self.email = self.txt
            self.state = 'alias'
            self.txt = ''
        elif tag == 'p' and self.state == 'alias':
            self.state = None
            aliases2 = map(lambda s: s.strip(), self.txt.lower().replace('\n',' ').replace('(','').replace(')','').split('-'))
            if len(aliases2) > 1: aliases2.append(''.join(aliases2))
            for alias in aliases2:
                if alias in userLU:
                    print "UPDATE users SET email = '%s' WHERE id = %d;" % (self.email, userLU[alias])
                    users[userLU[alias]-1]['email'] = self.email
                    return
            print '-- ERROR : No user named ' + ', '.join(aliases2)

parser = SpreadsheetParser()
f = open(os.path.expanduser('~/Documents/TWC Admin2.html'))
parser.feed(f.read())
f.close()

parser = EMailParser();
f = open(os.path.expanduser('~/Documents/2Member Forum emails, Slack.html'))
parser.feed(f.read())
f.close()

for userRec in users:
    if 'email' not in userRec:
        print "-- ERROR: User %(alias)s has no email" % userRec
print "-- Patches"
print "UPDATE users SET email = 'nlancier@gmail.com' WHERE alias = 'lancier';"
