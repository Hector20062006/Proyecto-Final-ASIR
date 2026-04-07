#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Servo.h>

LiquidCrystal_I2C lcd(0x27, 16, 2);
Servo servoMotor;

const int SERVO_PIN = 9;

void setup() {
  Serial.begin(9600);

  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Listo");

  servoMotor.attach(SERVO_PIN);
  servoMotor.write(105); // tu cerrada inicial (ajústalo si quieres)
}

void loop() {
  if (!Serial.available()) return;

  String msg = Serial.readStringUntil('\n');
  msg.trim();

  // Esperamos: angulo|linea1|linea2
  int p1 = msg.indexOf('|');
  if (p1 < 0) return;

  int p2 = msg.indexOf('|', p1 + 1); // segundo separador

  String anguloStr = msg.substring(0, p1);
  String l1 = "";
  String l2 = "";

  if (p2 >= 0) {
    l1 = msg.substring(p1 + 1, p2);
    l2 = msg.substring(p2 + 1);
  } else {
    // Si solo llega angulo|texto, lo ponemos en la línea 1
    l1 = msg.substring(p1 + 1);
    l2 = "";
  }

  int angulo = anguloStr.toInt();
  if (angulo < 0) angulo = 0;
  if (angulo > 180) angulo = 180;

  servoMotor.write(angulo);

  l1.trim(); l2.trim();
  if (l1.length() > 16) l1 = l1.substring(0, 16);
  if (l2.length() > 16) l2 = l2.substring(0, 16);

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(l1);
  lcd.setCursor(0, 1);
  lcd.print(l2);
}