package com.xpmsns.modules;
import android.app.Activity;
import android.annotation.TargetApi;
import android.os.Build;
import android.os.Bundle;
import android.view.View;
import com.facebook.common.logging.FLog;
import com.facebook.react.common.ReactConstants;
import com.facebook.react.bridge.GuardedRunnable;
import com.facebook.react.bridge.ReactApplicationContext;
import com.facebook.react.bridge.ReactContextBaseJavaModule;
import com.facebook.react.bridge.ReactMethod;
import com.facebook.react.bridge.UiThreadUtil;

import java.util.HashMap;
import java.util.Map;

public class WindowModule extends ReactContextBaseJavaModule {
    private static final String DURATION_SHORT="SHORT";
    private static final String DURATION_LONG="LONG";
    public WindowModule(ReactApplicationContext reactContext) {
        super(reactContext);
    }

    @Override
    public String getName() {
        return "Window";
    }
    @Override
    public Map<String, Object> getConstants() {
        final Map<String, Object> constants = new HashMap<>();
        return constants;
    }

    @ReactMethod
    public void setNavigationBarColor( final int color ) {
       final Activity activity = getCurrentActivity();
       if (activity == null) {
         FLog.w(ReactConstants.TAG, "WindowModule: Ignored navigation bar change, current activity is null.");
         return;
       }

      if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
        UiThreadUtil.runOnUiThread(
            new GuardedRunnable(getReactApplicationContext()) {
              @TargetApi(Build.VERSION_CODES.LOLLIPOP)
              @Override
              public void runGuarded() {
                activity.getWindow().setNavigationBarColor(color);
              }
        });
      }
    }

    @ReactMethod
    public void setStatusBarColor( final int color ) {
       final Activity activity = getCurrentActivity();
       if (activity == null) {
         FLog.w(ReactConstants.TAG, "WindowModule: Ignored status bar change, current activity is null.");
         return;
       }

      if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
        UiThreadUtil.runOnUiThread(
            new GuardedRunnable(getReactApplicationContext()) {
              @TargetApi(Build.VERSION_CODES.LOLLIPOP)
              @Override
              public void runGuarded() {
                activity.getWindow().setStatusBarColor(color);
              }
        });
      }
    }

}
