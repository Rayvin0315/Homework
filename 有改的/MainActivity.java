package com.example.iot_chatbot_practice;

import android.os.Bundle;
import android.widget.Button;
import android.widget.EditText;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;
import java.util.Map;
import java.util.HashMap;

public class MainActivity extends AppCompatActivity {

    private RecyclerView recyclerView;
    private ChatAdapter chatAdapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        recyclerView = findViewById(R.id.recycler_view_chat);
        chatAdapter = new ChatAdapter(new ArrayList<>());
        LinearLayoutManager layoutManager = new LinearLayoutManager(this);
        recyclerView.setLayoutManager(layoutManager);
        recyclerView.setAdapter(chatAdapter);

        // 獲取聊天泡泡之間的間距並設置給RecyclerView
        int spacingBetweenBubbles = getResources().getDimensionPixelSize(R.dimen.spacing_between_bubbles);
        recyclerView.addItemDecoration(new ChatItemDecoration(spacingBetweenBubbles));

        Button sendButton = findViewById(R.id.button_send);
        EditText inputEditText = findViewById(R.id.edit_text_input);

        sendButton.setOnClickListener(v -> {
            // 獲取用戶輸入的消息，添加到聊天中，然後清空輸入框
            String userMessage = inputEditText.getText().toString().trim();
            if (!userMessage.isEmpty()) {
                addMessageToChat(new ChatMessage(userMessage, true));
                // 調用askChatGpt方法，發送消息到ChatGPT
                askChatGpt(userMessage);
                inputEditText.setText("");
            }
        });
    }

    // 將消息添加到聊天中
    private void addMessageToChat(ChatMessage chatMessage) {
        chatAdapter.addMessage(chatMessage);
        recyclerView.scrollToPosition(chatAdapter.getItemCount() - 1);
    }

    // 請求ChatGpt生成回覆
    private void askChatGpt(String userPrompt) {
        // 創建OpenAIAPIClient實例並發送請求
        OpenAIAPIClient.OpenAIAPIService apiService = OpenAIAPIClient.create();

        // 創建系統消息對象，指定翻譯為Assistant
        Message systemMessage = new Message("system", "You are Assistant");

        // 創建用戶消息對象
        Message userMessage = new Message("user", userPrompt);

        // 添加系統消息和用戶消息到消息列表
        List<Message> messageList = new ArrayList<>();
        messageList.add(systemMessage);
        messageList.add(userMessage);

        // 添加 Function 設定
        List<Function> functions = new ArrayList<>();
        // 定義需要調用的 Function 參數
        Map<String, FunctionParameterProperty> properties = new HashMap<>();
        properties.put("to_address", new FunctionParameterProperty("string", "To address for the delivery"));
        properties.put("order", new FunctionParameterProperty("string", "The detail of the order"));
        properties.put("date", new FunctionParameterProperty("string", "The date for delivery"));
        properties.put("notes", new FunctionParameterProperty("string", "Any delivery notes"));

        FunctionParameter parameters = new FunctionParameter("object", properties);
        functions.add(new Function("order_detail", "template to capture an order.", parameters));

        OpenAIRequestModel requestModel = new OpenAIRequestModel("gpt-4o-mini", messageList, 0.7f);
        requestModel.setFunctions(functions);
        requestModel.setFunctionCall("auto");

        // 使用Retrofit發送請求並處理回覆
        Call<OpenAIResponseModel> call = apiService.getCompletion(requestModel);
        call.enqueue(new Callback<OpenAIResponseModel>() {
            @Override
            public void onResponse(Call<OpenAIResponseModel> call, Response<OpenAIResponseModel> response) {
                if (response.isSuccessful() && response.body() != null) {
                    // 正常處理回應
                    OpenAIResponseModel responseBody = response.body();
                    String generatedText;

                    if ("function_call".equals(responseBody.getChoices()[0].getFinishReason())) {
                        // 提取 function_call 的結果
                        FunctionCall functionCall = responseBody.getChoices()[0].getMessage().getFunctionCall();

                        // 假設 functionCall.getArguments() 返回 JSON 字符串
                        String jsonArguments = functionCall.getArguments();

                        // 格式化JSON字符串以在每個鍵值對之間換行
                        generatedText = jsonArguments
                                .replace("{", "{\n\t")    // 在第一個 { 後面添加換行和縮進
                                .replace("}", "\n}")      // 在 } 前面添加換行
                                .replace(",", ",\n\t")    // 在每個逗號後面添加換行和縮進
                                .replace(":", ": ");      // 在冒號後面添加一個空格

                    } else {
                        generatedText = responseBody.getChoices()[0].getMessage().getContent();
                    }


                    addMessageToChat(new ChatMessage(generatedText, false));
                } else {
                    try {
                        String errorMessage = response.errorBody() != null ? response.errorBody().string() : "Unknown error";
                        addMessageToChat(new ChatMessage("API error: " + errorMessage, false));
                    } catch (Exception e) {
                        addMessageToChat(new ChatMessage("API error: Unable to parse error message", false));
                    }
                }
            }

            @Override
            public void onFailure(Call<OpenAIResponseModel> call, Throwable t) {
                addMessageToChat(new ChatMessage("API onFailure: " + t.getMessage(), false));
            }
        });
    }

}
