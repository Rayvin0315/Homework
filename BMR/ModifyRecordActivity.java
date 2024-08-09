package com.example.bmrcalculator;

import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;

import org.json.JSONException;
import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

public class ModifyRecordActivity extends AppCompatActivity {

    private EditText editName, editAge, editWeight, editHeight;
    private RadioGroup radioGroupGender;
    private Button buttonUpdate;
    private RequestQueue requestQueue;
    private int recordId;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_modify_record);

        editName = findViewById(R.id.edit_name);
        editAge = findViewById(R.id.edit_age);
        editWeight = findViewById(R.id.edit_weight);
        editHeight = findViewById(R.id.edit_height);
        radioGroupGender = findViewById(R.id.radio_group_gender);
        buttonUpdate = findViewById(R.id.button_update);

        requestQueue = Volley.newRequestQueue(this);

        // Get record ID from intent
        recordId = getIntent().getIntExtra("record_id", -1);
        if (recordId == -1) {
            Toast.makeText(this, "Invalid record ID", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }

        // Load record data
        loadRecordData(recordId);

        buttonUpdate.setOnClickListener(v -> updateRecord());
    }

    private void loadRecordData(int id) {
        String url = "http://10.0.2.2/bmr_api/read_single.php"; // Ensure this endpoint is correct
        StringRequest stringRequest = new StringRequest(Request.Method.POST, url,
                response -> {
                    try {
                        JSONObject record = new JSONObject(response);
                        editName.setText(record.optString("name", ""));
                        editAge.setText(String.valueOf(record.optInt("age", 0)));
                        editWeight.setText(String.valueOf(record.optDouble("weight", 0.0)));
                        editHeight.setText(String.valueOf(record.optDouble("height", 0.0)));

                        // Set gender radio button
                        String gender = record.optString("gender", "Male");
                        if ("Male".equals(gender)) {
                            radioGroupGender.check(R.id.radio_male);
                        } else {
                            radioGroupGender.check(R.id.radio_female);
                        }
                    } catch (JSONException e) {
                        Toast.makeText(ModifyRecordActivity.this, "Error parsing record data", Toast.LENGTH_SHORT).show();
                    }
                },
                error -> Toast.makeText(ModifyRecordActivity.this, "Error loading record data", Toast.LENGTH_SHORT).show()) {
            @Override
            protected Map<String, String> getParams() {
                Map<String, String> params = new HashMap<>();
                params.put("id", String.valueOf(id));
                return params;
            }
        };

        requestQueue.add(stringRequest);
    }

    private void updateRecord() {
        String name = editName.getText().toString().trim();
        int age = Integer.parseInt(editAge.getText().toString().trim());
        double weight = Double.parseDouble(editWeight.getText().toString().trim());
        double height = Double.parseDouble(editHeight.getText().toString().trim());
        int selectedId = radioGroupGender.getCheckedRadioButtonId();
        RadioButton selectedRadioButton = findViewById(selectedId);
        String gender = selectedRadioButton.getText().toString();

        // Recalculate BMR
        double bmr;
        if (gender.equals("Male")) {
            bmr = 88.362 + (13.397 * weight) + (4.799 * height) - (5.677 * age);
        } else {
            bmr = 447.593 + (9.247 * weight) + (3.098 * height) - (4.330 * age);
        }

        String url = "http://10.0.2.2/bmr_api/update.php"; // Ensure this endpoint updates the record
        StringRequest stringRequest = new StringRequest(Request.Method.POST, url,
                response -> {
                    Toast.makeText(ModifyRecordActivity.this, "Record updated!", Toast.LENGTH_SHORT).show();
                    finish();
                },
                error -> Toast.makeText(ModifyRecordActivity.this, "Error updating record", Toast.LENGTH_SHORT).show()) {
            @Override
            protected Map<String, String> getParams() {
                Map<String, String> params = new HashMap<>();
                params.put("id", String.valueOf(recordId));
                params.put("name", name);
                params.put("age", String.valueOf(age));
                params.put("weight", String.valueOf(weight));
                params.put("height", String.valueOf(height));
                params.put("gender", gender);
                params.put("bmr", String.valueOf(bmr)); // Add recalculated BMR to parameters
                return params;
            }
        };

        requestQueue.add(stringRequest);
    }

}





