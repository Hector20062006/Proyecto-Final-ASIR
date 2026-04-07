#include <Servo.h>

Servo servo;
const byte SERVO_PIN = 9;

String cmd = "";

void setup() {
  Serial.begin(9600);
  servo.attach(SERVO_PIN);
  servo.write(90);          // posición inicial
  delay(500);

  Serial.println("READY");  // para que Python sepa que ya está listo
}

void loop() {
  while (Serial.available() > 0) {
    char c = Serial.read();

    // Fin de línea -> procesar comando
    if (c == '\n' || c == '\r') {
      if (cmd.length() > 0) {
        procesar(cmd);
        cmd = "";
      }
    } else {
      cmd += c;
    }
  }
}

void procesar(String s) {
  s.trim();
  if (s.length() == 0) return;

  // Permite "S90" o "90"
  if (s[0] == 'S' || s[0] == 's') {
    s.remove(0, 1);
    s.trim();
  }

  int ang = s.toInt();

  if (ang >= 0 && ang <= 180) {
    servo.write(ang);
    Serial.print("OK ");
    Serial.println(ang);
  } else {
    Serial.println("ERR");
  }
}