package com.example.bmrcalculator;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;

import java.util.HashMap;
import java.util.Map;

public class MainActivity extends AppCompatActivity {

    private EditText editName, editAge, editWeight, editHeight;
    private RadioGroup radioGroupGender;
    private Button buttonCalculate, buttonSave, buttonView;
    private TextView textResult, textRecords;
    private RequestQueue requestQueue;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        editName = findViewById(R.id.edit_name);
        editAge = findViewById(R.id.edit_age);
        editWeight = findViewById(R.id.edit_weight);
        editHeight = findViewById(R.id.edit_height);
        radioGroupGender = findViewById(R.id.radio_group_gender);
        buttonCalculate = findViewById(R.id.button_calculate);
        buttonSave = findViewById(R.id.button_save);
        buttonView = findViewById(R.id.button_view);
        textResult = findViewById(R.id.text_result);
        textRecords = findViewById(R.id.text_records);

        requestQueue = Volley.newRequestQueue(this);

        buttonCalculate.setOnClickListener(v -> calculateBMR());
        buttonSave.setOnClickListener(v -> saveRecord());
        buttonView.setOnClickListener(v -> {
            Intent intent = new Intent(MainActivity.this, RecordsActivity.class);
            startActivity(intent);
        });
    }

    private void calculateBMR() {
        String name = editName.getText().toString();
        int age = Integer.parseInt(editAge.getText().toString());
        float weight = Float.parseFloat(editWeight.getText().toString());
        float height = Float.parseFloat(editHeight.getText().toString());

        int selectedId = radioGroupGender.getCheckedRadioButtonId();
        RadioButton radioButton = findViewById(selectedId);
        String gender = radioButton.getText().toString();

        float bmr;
        if (gender.equals("Male")) {
            bmr = 88.362f + (13.397f * weight) + (4.799f * height) - (5.677f * age);
        } else {
            bmr = 447.593f + (9.247f * weight) + (3.098f * height) - (4.330f * age);
        }

        textResult.setText("BMR: " + bmr + " calories/day");
    }

    private void saveRecord() {
        String url = "http://10.0.2.2/bmr_api/create.php"; // Use 10.0.2.2 for localhost in emulator

        String name = editName.getText().toString();
        String age = editAge.getText().toString();
        String weight = editWeight.getText().toString();
        String height = editHeight.getText().toString();
        int selectedId = radioGroupGender.getCheckedRadioButtonId();
        RadioButton radioButton = findViewById(selectedId);
        String gender = radioButton.getText().toString();

        // Calculate BMR
        float bmr;
        if (gender.equals("Male")) {
            bmr = 88.362f + (13.397f * Float.parseFloat(weight)) + (4.799f * Float.parseFloat(height)) - (5.677f * Integer.parseInt(age));
        } else {
            bmr = 447.593f + (9.247f * Float.parseFloat(weight)) + (3.098f * Float.parseFloat(height)) - (4.330f * Integer.parseInt(age));
        }

        StringRequest stringRequest = new StringRequest(Request.Method.POST, url,
                new Response.Listener<String>() {
                    @Override
                    public void onResponse(String response) {
                        Toast.makeText(MainActivity.this, "Record saved!", Toast.LENGTH_SHORT).show();
                        editName.setText("");
                        editAge.setText("");
                        editWeight.setText("");
                        editHeight.setText("");
                        radioGroupGender.clearCheck();
                    }
                },
                new Response.ErrorListener() {
                    @Override
                    public void onErrorResponse(VolleyError error) {
                        Toast.makeText(MainActivity.this, "Error saving record", Toast.LENGTH_SHORT).show();
                    }
                }) {
            @Override
            protected Map<String, String> getParams() {
                Map<String, String> params = new HashMap<>();
                params.put("name", name);
                params.put("age", age);
                params.put("weight", weight);
                params.put("height", height);
                params.put("gender", gender);
                params.put("bmr", String.valueOf(bmr)); // Add BMR to parameters
                return params;
            }
        };

        // Ensure requestQueue is initialized
        if (requestQueue == null) {
            requestQueue = Volley.newRequestQueue(this);
        }

        requestQueue.add(stringRequest);
    }
}

