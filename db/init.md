http://www.yiibai.com/sqlite/

sqlit3 app.db
.databases
.tables
.quit
CREATE TABLE POSTS(
   ID INT PRIMARY KEY     NOT NULL,
   NAME           CHAR(50)    NOT NULL,
   ADDRESS        CHAR(50)
);
INSERT INTO POSTS (ID, NAME, ADDRESS) VALUES (1,"titile1","add1");
INSERT INTO POSTS (ID, NAME, ADDRESS) VALUES (2,"titile2","add2");
INSERT INTO POSTS (ID, NAME, ADDRESS) VALUES (3,"titile3","add3");
INSERT INTO POSTS (ID, NAME, ADDRESS) VALUES (4,"titile4","add4");

.header on
.mode column
select * from posts;