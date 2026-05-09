#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <Servo.h>

LiquidCrystal_I2C lcd(0x27, 16, 2);
Servo servoMotor;

const int SERVO_PIN = 9;
const int PIN_ROJO_ENTRADA = 2;
const int PIN_VERDE_ENTRADA = 3;
const int PIN_ROJO_SALIDA = 4;
const int PIN_VERDE_SALIDA = 5;

int lastAngle = 105; // Guardamos la última posición conocida


void setup() {
  Serial.begin(9600);

  lcd.init();
  lcd.backlight();
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Listo");

  servoMotor.attach(SERVO_PIN);
  servoMotor.write(105); // tu cerrada inicial (ajústalo si quieres)
  delay(500);            // Esperamos que llegue a la posición
  servoMotor.detach();   // Lo apagamos para que no suene

  pinMode(PIN_ROJO_ENTRADA, OUTPUT);
  pinMode(PIN_VERDE_ENTRADA, OUTPUT);
  pinMode(PIN_ROJO_SALIDA, OUTPUT);
  pinMode(PIN_VERDE_SALIDA, OUTPUT);

  // Reposo inicial: Rojos encendidos, verdes apagados
  digitalWrite(PIN_ROJO_ENTRADA, HIGH);
  digitalWrite(PIN_VERDE_ENTRADA, LOW);
  digitalWrite(PIN_ROJO_SALIDA, HIGH);
  digitalWrite(PIN_VERDE_SALIDA, LOW);
}

void loop() {
  if (!Serial.available()) return;

  String msg = Serial.readStringUntil('\n');
  msg.trim();

  // Esperamos: angulo|linea1|linea2|estado_led
  int p1 = msg.indexOf('|');
  if (p1 < 0) return;

  int p2 = msg.indexOf('|', p1 + 1); // segundo separador
  int p3 = -1;
  if (p2 >= 0) p3 = msg.indexOf('|', p2 + 1); // tercer separador (estado led)

  String anguloStr = msg.substring(0, p1);
  String l1 = "";
  String l2 = "";
  String estadoLed = "0";

  if (p2 >= 0 && p3 >= 0) {
    l1 = msg.substring(p1 + 1, p2);
    l2 = msg.substring(p2 + 1, p3);
    estadoLed = msg.substring(p3 + 1);
  } else if (p2 >= 0) {
    // Solo llegaron angulo|texto1|texto2
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

  // Movimiento suave del servo sin ruido en reposo
  if (lastAngle != angulo) {
    servoMotor.attach(SERVO_PIN);
    servoMotor.write(lastAngle); // Asegura que empiece desde donde se quedó
    
    if (lastAngle < angulo) {
      for (int pos = lastAngle; pos <= angulo; pos++) {
        servoMotor.write(pos);
        delay(15); // Ajusta este valor (15ms) para hacer el giro más lento o rápido
      }
    } else {
      for (int pos = lastAngle; pos >= angulo; pos--) {
        servoMotor.write(pos);
        delay(15);
      }
    }
    
    lastAngle = angulo;
    delay(200); // Espera a que la barrera se asiente físicamente
    servoMotor.detach(); // Apaga el pulso PWM para que el motor deje de zumbar
  }

  l1.trim(); l2.trim(); estadoLed.trim();
  if (l1.length() > 16) l1 = l1.substring(0, 16);
  if (l2.length() > 16) l2 = l2.substring(0, 16);

  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print(l1);
  lcd.setCursor(0, 1);
  lcd.print(l2);

  // Control de LEDs según estadoLed
  if (estadoLed == "1") {
    // Entrando: Verde Entrada ON, Rojo Entrada OFF
    digitalWrite(PIN_ROJO_ENTRADA, LOW);
    digitalWrite(PIN_VERDE_ENTRADA, HIGH);
    digitalWrite(PIN_ROJO_SALIDA, HIGH);
    digitalWrite(PIN_VERDE_SALIDA, LOW);
  } else if (estadoLed == "2") {
    // Saliendo: Verde Salida ON, Rojo Salida OFF
    digitalWrite(PIN_ROJO_ENTRADA, HIGH);
    digitalWrite(PIN_VERDE_ENTRADA, LOW);
    digitalWrite(PIN_ROJO_SALIDA, LOW);
    digitalWrite(PIN_VERDE_SALIDA, HIGH);
  } else {
    // Reposo: Ambos rojos ON
    digitalWrite(PIN_ROJO_ENTRADA, HIGH);
    digitalWrite(PIN_VERDE_ENTRADA, LOW);
    digitalWrite(PIN_ROJO_SALIDA, HIGH);
    digitalWrite(PIN_VERDE_SALIDA, LOW);
  }
}