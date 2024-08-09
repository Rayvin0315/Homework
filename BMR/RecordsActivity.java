package com.example.bmrcalculator;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.ListView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.android.volley.Request;
import com.android.volley.RequestQueue;
import com.android.volley.Response;
import com.android.volley.VolleyError;
import com.android.volley.toolbox.HttpHeaderParser;
import com.android.volley.toolbox.JsonArrayRequest;
import com.android.volley.toolbox.StringRequest;
import com.android.volley.toolbox.Volley;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

public class RecordsActivity extends AppCompatActivity {

    private ListView listViewRecords;
    private RequestQueue requestQueue;
    private ArrayAdapter<String> adapter;
    private ArrayList<JSONObject> recordsList;
    private Button buttonBack, buttonDelete, buttonModify;
    private int selectedRecordId = -1;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_records);

        listViewRecords = findViewById(R.id.list_view_records);
        buttonBack = findViewById(R.id.button_back);
        buttonDelete = findViewById(R.id.button_delete);
        buttonModify = findViewById(R.id.button_modify);

        recordsList = new ArrayList<>();
        adapter = new ArrayAdapter<>(this, android.R.layout.simple_list_item_1, new ArrayList<>());
        listViewRecords.setAdapter(adapter);

        requestQueue = Volley.newRequestQueue(this);

        loadRecords();

        listViewRecords.setOnItemClickListener((parent, view, position, id) -> {
            try {
                JSONObject selectedRecord = recordsList.get(position);
                selectedRecordId = selectedRecord.getInt("id");
                Toast.makeText(RecordsActivity.this, "Selected record ID: " + selectedRecordId, Toast.LENGTH_SHORT).show();
            } catch (JSONException e) {
                e.printStackTrace();
            }
        });

        buttonDelete.setOnClickListener(v -> deleteRecord());
        buttonModify.setOnClickListener(v -> {
            if (selectedRecordId == -1) {
                Toast.makeText(RecordsActivity.this, "Select a record to modify", Toast.LENGTH_SHORT).show();
            } else {
                Intent intent = new Intent(RecordsActivity.this, ModifyRecordActivity.class);
                intent.putExtra("record_id", selectedRecordId);
                startActivity(intent);
            }
        });

        buttonBack.setOnClickListener(v -> {
            Intent intent = new Intent(RecordsActivity.this, MainActivity.class);
            startActivity(intent);
            finish();
        });
    }

    private void loadRecords() {
        String url = "http://10.0.2.2/bmr_api/read.php"; // Use 10.0.2.2 for localhost in emulator

        JsonArrayRequest jsonArrayRequest = new JsonArrayRequest(Request.Method.GET, url, null,
                new Response.Listener<JSONArray>() {
                    @Override
                    public void onResponse(JSONArray response) {
                        parseRecords(response);
                    }
                },
                new Response.ErrorListener() {
                    @Override
                    public void onErrorResponse(VolleyError error) {
                        adapter.clear();
                        adapter.add("Error: " + error.getMessage());
                    }
                }) {
            @Override
            protected Response<JSONArray> parseNetworkResponse(com.android.volley.NetworkResponse response) {
                try {
                    String jsonString = new String(response.data, "UTF-8");
                    JSONArray jsonArray = new JSONArray(jsonString);
                    return Response.success(jsonArray, HttpHeaderParser.parseCacheHeaders(response));
                } catch (Exception e) {
                    return Response.error(new VolleyError("Parsing error"));
                }
            }
        };

        requestQueue.add(jsonArrayRequest);
    }

    private void parseRecords(JSONArray response) {
        recordsList.clear();
        ArrayList<String> displayList = new ArrayList<>();
        try {
            for (int i = 0; i < response.length(); i++) {
                JSONObject record = response.getJSONObject(i);
                recordsList.add(record);

                double bmr = record.optDouble("bmr", 0.0);
                String recordString = "ID: " + record.getInt("id") +
                        " | Name: " + record.getString("name") +
                        " | Age: " + record.getInt("age") +
                        " | Weight: " + record.getDouble("weight") +
                        " | Height: " + record.getDouble("height") +
                        " | Gender: " + record.getString("gender") +
                        " | BMR: " + bmr;

                displayList.add(recordString);
            }
        } catch (JSONException e) {
            displayList.add("Error parsing records: " + e.getMessage());
        }
        adapter.clear();
        adapter.addAll(displayList);
    }


    private void deleteRecord() {
        if (selectedRecordId == -1) {
            Toast.makeText(this, "Select a record to delete", Toast.LENGTH_SHORT).show();
            return;
        }

        String url = "http://10.0.2.2/bmr_api/delete.php"; // Use 10.0.2.2 for localhost in emulator

        StringRequest stringRequest = new StringRequest(Request.Method.POST, url,
                response -> {
                    Toast.makeText(RecordsActivity.this, "Record deleted!", Toast.LENGTH_SHORT).show();
                    selectedRecordId = -1; // Reset selected record ID
                    loadRecords(); // Refresh the records list
                },
                error -> Toast.makeText(RecordsActivity.this, "Error deleting record", Toast.LENGTH_SHORT).show()) {
            @Override
            protected Map<String, String> getParams() {
                Map<String, String> params = new HashMap<>();
                params.put("id", String.valueOf(selectedRecordId));
                return params;
            }
        };

        requestQueue.add(stringRequest);
    }
}













