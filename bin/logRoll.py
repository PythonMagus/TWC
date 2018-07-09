#!/usr/bin/python
# Rolls the log files and sends a report
import re, smtplib,time,os, mysql.connector

USER = 'reports@twc.redwaratah.com'
PASS = 'f-4Wmg8f@Es4'
TEST = False
LOGFILE = '/home/maneschi/logs/twc0.6.log'
NOW = time.strftime('%d-%B %H:%m')
LASTWEEK = time.strftime('%d-%B %H:%M', time.localtime(time.time() - 7 * 86400))
SQL_LW = time.strftime("'%Y-%m-%d %H:%M:%S'", time.localtime(time.time() - 7 * 86400))
STYLE = '''
table {
    border: 1px solid #aaa;
    padding: 0px;
}
table tr th {
    font-size: 18px;
    color: #ff8;
    background-color: #666;
    width:280px;
    text-align: left;
}
td.Metric {
    font-weight: bold;
    font-size: 14px;
    background-color: #eef;
}
td.Stat {
    text-align: right;
    font-size: 14px;
    padding-right: 12px;
}
td.Total {
    font-weight: bold;
}
ul {
    margin-top: 0px;
}
h3 {
    font-size: 18px;
    color: #903;
    margin-bottom: 2px;
}
'''
if os.path.exists(LOGFILE + '.9'):
    os.unlink(LOGFILE + '.9')
for n in range(8, -1, -1):
    if os.path.exists('%s.%d' % (LOGFILE, n)):
        os.rename('%s.%d' % (LOGFILE, n), '%s.%d' % (LOGFILE, n + 1))
logData = '-- No usage --'
if os.path.exists(LOGFILE):
    f = open(LOGFILE)
    logData = f.read()
    f.close()
    os.rename(LOGFILE, LOGFILE + '.0')

tableRows = []
conn = mysql.connector.connect(host='localhost',database='maneschi_twc',user='maneschi_twc',password='k,={iJ5e}O!Q')
cursor = conn.cursor()
for label, sql, mapping in (
    ('New users', 'select id, alias from users where created > %s order by alias' % SQL_LW, 
        lambda r: '<a href="https://twc.redwaratah.com/user.php?id=%d">%s</a>' % tuple(r)),
    ('Active users', 'select id, alias from users where lastLogin > %s order by alias' % SQL_LW, 
        lambda r: '<a href="https://twc.redwaratah.com/user.php?id=%d">%s</a>' % tuple(r)),
    ('New battles', 'select b.id, g.type, b.name from battles b JOIN gametypes g ON b.typeid = g.id where started > %s order by g.type, b.name' % SQL_LW, 
        lambda r: '<a href="https://twc.redwaratah.com/battle.php?id=%d">%s %s</a>' % tuple(r)),
    ('Completed battles', 'select b.id, g.type, b.name from battles b JOIN gametypes g ON b.typeid = g.id where ended > %s order by g.type, b.name' % SQL_LW, 
        lambda r: '<a href="https://twc.redwaratah.com/battle.php?id=%d">%s %s</a>' % tuple(r)),
    ('Current battles', 'select count(id) from battles where started <= NOW() and ISNULL(ended)', 
        lambda r: '%d Battles' % r[0]),
    ('Current battles last week', 'select count(id) from battles where started <= %s and (ISNULL(ended) OR ended > %s)' % (SQL_LW, SQL_LW), 
        lambda r: '%d Battles' % r[0])):
    cursor.execute(sql)
    cell = []
    row = cursor.fetchone()
    while row:
        cell.append(mapping(row))
        row = cursor.fetchone()
    if len(cell):
        tableRows.append('<tr><th>%s</th><td class="%s">%s</td></tr>' % (label, 'Stat' if label[:7] == 'Current' else 'Metric', ','.join(cell)))

smtp = smtplib.SMTP('mail.redwaratah.com', 25)
smtp.login(USER, PASS)
recipients = 'nlancier@gmail.com,pythonmagus@redwaratah.com,lordlau1@gmail.com,nedfn1@comcast.net'.split(',')
if TEST: recipients = 'maurice@redwaratah.com',
smtp.sendmail(USER, recipients, '''Subject: TWC OoR Weekly Report
Content-Type: text/html
From: Do not Reply <%s>
To: Site Admins
Date: %s

<html>
<head>
<style type="text/css">
%s
</style>
</head>
<body>
<table>
%s
</table>
<hr width="80%%"/>
<h2>Weekly Log</h2>
<pre>%s</pre>
</body></html>''' % (USER,time.strftime('%a, %d %b %Y %H:%M:%S +1000'),STYLE, '\n'.join(tableRows), logData))
smtp.quit()                


    
