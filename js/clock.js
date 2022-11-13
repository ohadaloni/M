function clock() {
  const time = new Date();
  Dn = time.getDay();
  Y = time.getFullYear();
  m = time.getMonth();
  d = time.getDate();
  G = time.getHours();
  i = time.getMinutes();
  s = time.getSeconds();
  days = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
  D = days[Dn];
  m++;
  m = pad0(m);
  d = pad0(d);
  i = pad0(i);
  s = pad0(s);
  document.getElementById('clock').innerHTML = D + " " + Y + "-" + m + "-" + d + " " +  G + ":" + i + ":" + s;
  setTimeout(clock, 1000);
}

function pad0(i) {
  if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
  return i;
}
