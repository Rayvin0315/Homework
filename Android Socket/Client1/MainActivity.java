package com.example.client1; // or package com.example.client2;

import android.os.Bundle;
import android.os.StrictMode;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import java.io.*;
import java.net.*;

public class MainActivity extends AppCompatActivity {
    private Socket socket;
    private PrintWriter out;
    private BufferedReader in;

    private EditText etClientName, etServerIp, etServerPort, etMessage;
    private Button btnConnect, btnDisconnect, btnSend;
    private TextView tvChat;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Allow network operations on the main thread for simplicity
        StrictMode.ThreadPolicy policy = new StrictMode.ThreadPolicy.Builder().permitAll().build();
        StrictMode.setThreadPolicy(policy);

        etClientName = findViewById(R.id.etClientName);
        etServerIp = findViewById(R.id.etServerIp);
        etServerPort = findViewById(R.id.etServerPort);
        etMessage = findViewById(R.id.etMessage);
        btnConnect = findViewById(R.id.btnConnect);
        btnDisconnect = findViewById(R.id.btnDisconnect);
        btnSend = findViewById(R.id.btnSend);
        tvChat = findViewById(R.id.tvChat);

        btnConnect.setOnClickListener(v -> {
            if (isValidClientName()) {
                connectToServer();
            } else {
                Toast.makeText(MainActivity.this, "Please enter a valid name", Toast.LENGTH_SHORT).show();
            }
        });

        btnDisconnect.setOnClickListener(v -> disconnectServer());
        btnSend.setOnClickListener(v -> sendMessage());
    }

    private boolean isValidClientName() {
        String clientName = etClientName.getText().toString().trim();
        return !clientName.isEmpty();
    }

    private void connectToServer() {
        String clientName = etClientName.getText().toString().trim();
        String serverIp = etServerIp.getText().toString().trim();
        int serverPort;

        try {
            serverPort = Integer.parseInt(etServerPort.getText().toString().trim());
        } catch (NumberFormatException e) {
            Toast.makeText(MainActivity.this, "Invalid server port", Toast.LENGTH_SHORT).show();
            return;
        }

        new Thread(() -> {
            try {
                socket = new Socket(serverIp, serverPort);
                out = new PrintWriter(socket.getOutputStream(), true);
                in = new BufferedReader(new InputStreamReader(socket.getInputStream()));

                // Send client name to server
                out.println(clientName);

                runOnUiThread(() -> {
                    btnSend.setEnabled(true);
                    btnDisconnect.setEnabled(true);
                    tvChat.append("Connected to server at " + serverIp + ":" + serverPort + "\n");
                });

                String message;
                while (true) {
                    try {
                        message = in.readLine();
                        if (message == null) break; // End of stream
                        final String msg = message;
                        runOnUiThread(() -> {
                            tvChat.append(msg + "\n");
                            System.out.println("Received message: " + msg); // Log to console
                        });
                    } catch (IOException e) {
                        runOnUiThread(() -> tvChat.append("Connection error: " + e.getMessage() + "\n"));
                        break; // Exit loop on error
                    }
                }


            } catch (IOException e) {
                runOnUiThread(() -> {
                    tvChat.append("Error connecting to server\n");
                    tvChat.append("Exception: " + e.getMessage() + "\n");
                });
                e.printStackTrace();
            }
        }).start();
    }

    private void disconnectServer() {
        if (socket != null) {
            try {
                // Send a disconnect message to the server
                out.println("DISCONNECT " + etClientName.getText().toString().trim());

                // Close the socket
                socket.close();
                runOnUiThread(() -> {
                    tvChat.append("Disconnected from server\n");
                    btnSend.setEnabled(false);
                    btnDisconnect.setEnabled(false);
                });
            } catch (IOException e) {
                if (!e.getMessage().contains("Socket closed")) {
                    runOnUiThread(() -> {
                        tvChat.append("Error disconnecting from server\n");
                        tvChat.append("Exception: " + e.getMessage() + "\n");
                    });
                }
                e.printStackTrace();
            }
        }
    }



    private void sendMessage() {
        if (out != null) {
            String message = etMessage.getText().toString().trim();
            if (!message.isEmpty()) {
                out.println(message);
                tvChat.append("Me: " + message + "\n");
                etMessage.setText("");
            }
        }
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        disconnectServer(); // Ensure the socket is closed when the activity is destroyed
    }
}





















